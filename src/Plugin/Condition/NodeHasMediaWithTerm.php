<?php

namespace Drupal\dgi_members\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\media\MediaInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\islandora\IslandoraUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\dgi_members\DgiMembersEntityOperations;

/**
 * A condition to check if a node has media with a specified term.
 *
 * @Condition(
 *   id = "node_has_media_with_term",
 *   label = @Translation("Current node has media that has term with URI"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
 *   }
 * )
 */
class NodeHasMediaWithTerm extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Islandora utils.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected $utils;

  /**
   * Term storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Member operations utility helper.
   *
   * @var \Drupal\dgi_members\DgiMembersEntityOperations
   */
  protected $dgiMembersEntityOperations;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\islandora\IslandoraUtils $utils
   *   Islandora utils.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\dgi_members\DgiMembersEntityOperations $dgiMembersEntityOperations
   *   Member entity operation helper utilities.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    IslandoraUtils $utils,
    EntityTypeManagerInterface $entity_type_manager,
    DgiMembersEntityOperations $dgiMembersEntityOperations
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->utils = $utils;
    $this->entityTypeManager = $entity_type_manager;
    $this->dgiMembersEntityOperations = $dgiMembersEntityOperations;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('islandora.utils'),
      $container->get('entity_type.manager'),
      $container->get('dgi_members.entity_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      [
        'logic' => 'and',
        'uri' => NULL,
      ],
      parent::defaultConfiguration()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default = [];
    if (isset($this->configuration['uri']) && !empty($this->configuration['uri'])) {
      $uris = explode(',', $this->configuration['uri']);
      foreach ($uris as $uri) {
        $default[] = $this->utils->getTermForUri($uri);
      }
    }

    $form['term'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Media Term'),
      '#description' => $this->t('Only terms that have external URIs/URLs will appear here.'),
      '#tags' => TRUE,
      '#default_value' => $default,
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'islandora:external_uri',
    ];

    $form['logic'] = [
      '#type' => 'radios',
      '#title' => $this->t('Logic'),
      '#description' => $this->t('Whether to use AND or OR logic to evaluate multiple terms'),
      '#options' => [
        'and' => 'And',
        'or' => 'Or',
      ],
      '#default_value' => $this->configuration['logic'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Set URI for term if possible.
    $value = $form_state->getValue('term');
    $uris = [];
    if (!empty($value)) {
      foreach ($value as $target) {
        $tid = $target['target_id'];
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
        $uri = $this->utils->getUriForTerm($term);
        if ($uri) {
          $uris[] = $uri;
        }
      }
      if (!empty($uris)) {
        $this->configuration['uri'] = implode(',', $uris);
      }
    }

    $this->configuration['logic'] = $form_state->getValue('logic');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {

    if (empty($this->configuration['uri']) && !$this->isNegated()) {
      return TRUE;
    }

    $node = $this->getContextValue('node');

    if (!$node) {
      return FALSE;
    }

    $media = $this->utils->getMedia($node);

    $data = $this->evaluateMedia($media);
    if (!empty($media) && $data) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Evaluates if a media entity has the specified term(s).
   *
   * @param array of MediaInterface $media
   *   The entity to evalute.
   *
   * @return bool
   *   TRUE if entity has all the specified term(s), otherwise FALSE.
   */
  protected function evaluateMedia(array $media) {
    foreach ($media as $entity) {
      foreach ($entity->referencedEntities() as $referenced_entity) {
        if ($referenced_entity->getEntityTypeId() == 'taxonomy_term' && $referenced_entity->hasField(IslandoraUtils::EXTERNAL_URI_FIELD)) {
          $field = $referenced_entity->get(IslandoraUtils::EXTERNAL_URI_FIELD);
          if (!$field->isEmpty()) {
            $link = $field->first()->getValue();
            if ($link['uri'] == $this->configuration['uri']) {
              return $this->isNegated() ? FALSE : TRUE;
            }
          }
        }
      }
    }

    return $this->isNegated() ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The media associated with this node is not associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
    else {
      return $this->t('The media associated with node is associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
  }

}
