<?php

namespace Drupal\dgi_members\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_members\DgiMembersEntityOperationsInterface;
use Drupal\islandora\Plugin\Condition\NodeHasTerm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an object member 'Term' condition for nodes.
 *
 * @Condition(
 *   id = "node_compound_current_has_term",
 *   label = @Translation("Compound active member node has term with URI"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE, label = @Translation("node"))
 *   }
 * )
 */
class NodeCompoundCurrentHasTerm extends NodeHasTerm {

  /**
   * Member operations utility helper.
   *
   * @var \Drupal\dgi_members\DgiMembersEntityOperationsInterface
   */
  protected DgiMembersEntityOperationsInterface $dgiMembersEntityOperations;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration,$plugin_id, $plugin_definition);

    $instance->dgiMembersEntityOperations = $container->get('dgi_members.entity_service');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(
      [
        'param' => '',
      ],
      parent::defaultConfiguration()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['param'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Parameter'),
      '#description' => $this->t('The url parameter to indicate which member is active. If omitted, the first member ordered by weight will always be assumed.'),
      '#default_value' => $this->configuration['param'],
      '#required' => FALSE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['param'] = $form_state->getValue('param');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {

    if (empty($this->configuration['uri']) && !$this->isNegated()) {
      return TRUE;
    }

    $node = $this->dgiMembersEntityOperations->retrieveActiveMember(
      $this->configuration['param']
    );

    if (!$node) {
      return FALSE;
    }

    return $this->evaluateEntity($node);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (!empty($this->configuration['negate'])) {
      return $this->t('The node is not associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
    else {
      return $this->t('The node is associated with taxonomy term with uri @uri.', ['@uri' => $this->configuration['uri']]);
    }
  }

}
