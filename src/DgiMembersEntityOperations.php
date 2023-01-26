<?php

namespace Drupal\dgi_members;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Class DgiMembersEntityOperations.
 *
 * Utility service to perform compound object related operations.
 */
class DgiMembersEntityOperations {

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
   * DgiMembersEntityOperations constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\islandora\IslandoraUtils $islandoraUtils
   *   Utility functions from islandora core.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   */
  public function __construct(RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager, IslandoraUtils $islandoraUtils, RequestStack $requestStack) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->islandoraUtils = $islandoraUtils;
    $this->requestStack = $requestStack;
  }

  /**
   * Confirm the routeMatch node parameter has the 'Compound Object' term.
   *
   * @return bool
   *   TRUE if the current node in the route is a compound object, FALSE
   *   otherwise.
   */
  public function nodeFromRouteIsCompound() {
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
            elseif (
              dgi_members_treat_parent_as_first_sibling()
              &&
              dgi_members_entity_understood_as_compound($entity)
            ) {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Retrieve the first member of the given object or the node from url param.
   *
   * @return bool|NodeInterface
   *   FALSE if unable to retrieve an active member, or the member if present.
   */
  public function retrieveActiveMember($url_param = NULL) {
    if ($url_param) {
      $active_member_param = $this->requestStack->getCurrentRequest()->query->get($url_param);
      if ($active_member_param) {
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
   * Retrieve the first member of the contextual 'Node'.
   *
   * @return bool|NodeInterface
   *   FALSE if the member could not be retrieved, or the member object.
   */
  public function retrieveFirstOfMembers() {
    $nodes = $this->membersQueryExecute();

    if (empty($nodes)) {
      return FALSE;
    }

    return $this->entityTypeManager->getStorage('node')->load(reset($nodes));
  }

  /**
   * Retrieve 'members' of the current page object.
   *
   * @return bool|array
   *   An array of members for the given page object, or FALSE if none found.
   */
  public function membersQueryExecute() {
    $entity = $this->routeMatch->getParameter('node');

    if (!$entity) {
      return FALSE;
    }

    $to_return = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('field_member_of', $entity->id())
      ->sort('field_weight')
      ->execute();
    if (dgi_members_treat_parent_as_first_sibling()) {
      // Allow current entity to present as the first member of itself.
      array_unshift($to_return, $entity->id());
    }

    return $to_return;
  }

}
