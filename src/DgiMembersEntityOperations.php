<?php

namespace Drupal\dgi_members;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Instance of config factory.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected IslandoraUtils $islandoraUtils;

  /**
   * Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Static compound URI.
   *
   * @var string
   */
  const COMPOUND_URI = "http://vocab.getty.edu/aat/300242735";

  /**
   * Http Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructor.
   */
  public function __construct(
    RouteMatchInterface $routeMatch,
    EntityTypeManagerInterface $entityTypeManager,
    IslandoraUtils $islandoraUtils,
    RequestStack $requestStack,
  ) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->islandoraUtils = $islandoraUtils;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritDoc}
   */
  public function nodeFromRouteIsCompound() : bool {
    $entity = $this->routeMatch->getParameter('node');
    if ($entity instanceof NodeInterface) {
      if ($entity->hasField($this->islandoraUtils::MODEL_FIELD) && !$entity->get($this->islandoraUtils::MODEL_FIELD)->isEmpty()) {

        // Retrieve the 'model' term of the given page entity.
        $term = $entity
          ->get($this->islandoraUtils::MODEL_FIELD)
          ->first()
          ->get('entity')
          ->getTarget()
          ->getValue();

        // Ensure the 'term' is of an instance we expect, exists, and has a
        // value before proceeding.
        if ($term instanceof TermInterface) {
          if ($term->hasField($this->islandoraUtils::EXTERNAL_URI_FIELD) && !$term->get($this->islandoraUtils::EXTERNAL_URI_FIELD)->isEmpty()) {

            // Retrieve term info for evaluation.
            $term_info = $term
              ->get($this->islandoraUtils::EXTERNAL_URI_FIELD)
              ->first()
              ->getValue();

            if ($term_info && $term_info['uri'] == DgiMembersEntityOperations::COMPOUND_URI) {
              return TRUE;
            }
            elseif (dgi_members_entity_understood_as_non_compound_compound($entity)) {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function retrieveActiveMember($url_param = NULL) : FALSE|NodeInterface {
    if ($url_param) {
      $active_member_param = $this->requestStack->getCurrentRequest()->query->get($url_param);
      if ($active_member_param) {
        // Check active member is part of the current compound object.
        $node_ids = $this->membersQueryExecute();
        if (!in_array($active_member_param, $node_ids)) {
          return FALSE;
        }

        /** @var \Drupal\node\NodeInterface $active_member */
        $active_member = $this->entityTypeManager->getStorage('node')->load($active_member_param);
        if ($active_member) {
          return $active_member;
        }
      }
    }
    if ($this->nodeFromRouteIsCompound()) {
      return $this->retrieveFirstOfMembers();
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function retrieveFirstOfMembers() : FALSE|NodeInterface {
    $node_ids = $this->membersQueryExecute();

    if (empty($node_ids)) {
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
    }

    return $to_return;
  }

}
