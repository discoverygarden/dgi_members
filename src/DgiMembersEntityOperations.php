<?php

namespace Drupal\dgi_members;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Utility service to perform compound object related operations.
 */
class DgiMembersEntityOperations implements DgiMembersEntityOperationsInterface {

  /**
   * Static compound URI.
   *
   * @var string
   */
  const COMPOUND_URI = "http://vocab.getty.edu/aat/300242735";

  /**
   * Model field name.
   *
   * @var string
   */
  protected string $modelField;

  /**
   * External URI field name.
   *
   * @var string
   */
  protected string $externalUriField;

  /**
   * Constructor.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected IslandoraUtils $islandoraUtils,
    protected RequestStack $requestStack,
  ) {
    $this->modelField = $this->islandoraUtils::MODEL_FIELD;
    $this->externalUriField = $this->islandoraUtils::EXTERNAL_URI_FIELD;
  }

  /**
   * {@inheritDoc}
   */
  public function nodeFromRouteIsCompound() : bool {
    $entity = $this->routeMatch->getParameter('node');
    if (!$entity instanceof NodeInterface) {
      return FALSE;
    }

    if (!self::hasPopulatedField($entity, $this->modelField)) {
      return FALSE;
    }

    // Retrieve the 'model' term of the given page entity.
    /** @var \Drupal\taxonomy\TermInterface|null $term */
    $term = $entity->get($this->modelField)?->first()?->get('entity')?->getTarget()?->getValue();

    // Ensure the 'term' is of an instance we expect, exists, and has a
    // value before proceeding.
    if (!$term instanceof TermInterface) {
      return FALSE;
    }
    if (!self::hasPopulatedField($term, $this->externalUriField)) {
      return FALSE;
    }

    // Retrieve term info for evaluation.
    $term_info = $term->get($this->externalUriField)->first()?->getValue();

    if ($term_info && $term_info['uri'] === static::COMPOUND_URI) {
      return TRUE;
    }
    if (dgi_members_entity_understood_as_non_compound_compound($entity)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function retrieveActiveMember(?string $url_param = NULL) : FALSE|NodeInterface {
    if (($from_param = $this->getActiveMemberFromQueryParameter($url_param)) !== NULL) {
      return $from_param;
    }
    if ($this->nodeFromRouteIsCompound()) {
      return $this->retrieveFirstOfMembers();
    }
    return FALSE;
  }

  /**
   * Determine active member based on a passed query parameter.
   *
   * @param string|null $query_parameter
   *   The query parameter, if passed.
   *
   * @return false|\Drupal\node\NodeInterface|null
   *   Returns:
   *   - NULL if no parameter passed or contains an ID we failed to load,
   *   - FALSE if the given parameter does not appear to reference a node in the
   *     current compound; or,
   *   - a loaded node that is the active member.
   */
  private function getActiveMemberFromQueryParameter(?string $query_parameter) : null|false|NodeInterface {
    if (!$query_parameter) {
      return NULL;
    }
    $active_member_param = $this->requestStack->getCurrentRequest()->query->get($query_parameter);
    if ($active_member_param === NULL) {
      return NULL;
    }

    // Check active member is part of the current compound object.
    $node_ids = $this->membersQueryExecute();
    if ($node_ids && !in_array($active_member_param, $node_ids, FALSE)) {
      return FALSE;
    }

    /** @var \Drupal\node\NodeInterface $active_member */
    $active_member = $this->entityTypeManager->getStorage('node')->load($active_member_param);
    if ($active_member) {
      return $active_member;
    }

    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function retrieveFirstOfMembers() : FALSE|NodeInterface {
    $node_ids = $this->membersQueryExecute();

    if (!$node_ids) {
      return FALSE;
    }

    $node_id = reset($node_ids);
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($node_id);

    return $node;
  }

  /**
   * {@inheritDoc}
   */
  public function membersQueryExecute() : array|FALSE {
    $entity = $this->routeMatch->getParameter('node');

    if (!$entity) {
      return FALSE;
    }

    // This array ends up with nids for keys and values.
    $to_return = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('field_member_of', $entity->id())
      ->sort('field_weight')
      ->execute();

    // Special case for showing non-compound compound objects with media as
    // a member of their own set of nodes.
    if (
      dgi_members_treat_parent_as_first_sibling()
      && dgi_members_entity_understood_as_non_compound_compound($entity)
    ) {
      // Allow current entity to present as the first member of itself.
      array_unshift($to_return, $entity->id());
      // Keep the array keys and values as nids instead of letting the
      // array_unshift keys persist.
      $to_return = array_combine($to_return, $to_return);
    }

    return $to_return;
  }

  /**
   * Helper; determine if the given entity has the given field populated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to test.
   * @param string $field
   *   The field to test.
   *
   * @return bool
   *   TRUE if the entity is fieldable and has a value in the given field;
   *   otherwise, FALSE.
   */
  private static function hasPopulatedField(EntityInterface $entity, string $field) : bool {
    return $entity instanceof FieldableEntityInterface &&
      $entity->hasField($field) &&
      !$entity->get($field)->isEmpty();
  }

}
