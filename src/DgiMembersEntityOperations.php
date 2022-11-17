<?php

namespace Drupal\dgi_members;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\Entity\Node;

/**
 * Class CurrentGroup.
 *
 * Get the current group an entity belongs to.
 */
class DgiMembersEntityOperations {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Instance of config factory.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected $islandoraUtils;

  /**
   * Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Static compound URI.
   *
   * @var string
   */
  public static $compoundUri = "http://vocab.getty.edu/aat/300242735";

  /**
   * Http Request stack.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * CurrentGroup constructor.
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
   * Confirm a page related object has the 'Compound Object' term.
   *
   * @return bool
   *   TRUE if the current node in the route is a compound object, FALSE
   *   otherwise.
   */
  public function pageEntityIsCompoundObjectNode() {
    $entity = $this->routeMatch->getParameter('node');
    if ($entity instanceof Node) {

      // Find the terms on the node.
      $field_names = $this->islandoraUtils->getUriFieldNamesForTerms();
      $terms = array_filter($entity->referencedEntities(), function ($entity) use ($field_names) {
        if ($entity->getEntityTypeId() != 'taxonomy_term') {
          return FALSE;
        }

        foreach ($field_names as $field_name) {
          if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
            return TRUE;
          }
        }
        return FALSE;
      });

      // Get their URIs.
      $haystack = array_map(function ($term) {
        return $this->islandoraUtils->getUriForTerm($term);
      },
        $terms
      );

      // FALSE if there's no URIs on the node.
      if (empty($haystack)) {
        return FALSE;
      }

      if (count(array_intersect([DgiMembersEntityOperations::$compoundUri], $haystack)) > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Retrieve the first member of the given object or the node from url param.
   *
   * @return bool||node
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

    if ($this->pageEntityIsCompoundObjectNode()) {
      return $this->retrieveFirstOfMembers();
    }

    return FALSE;
  }

  /**
   * Retrieve the first member of the contextual 'Node'.
   *
   * @return bool||Node
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
   * @return BOOL|array
   *   An array of members for the given page object, or FALSE if none found.
   */
  public function membersQueryExecute() {
    $entity = $this->routeMatch->getParameter('node');

    if (!$entity) {
      return FALSE;
    }

    return $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('field_member_of', $entity->id())
      ->sort('field_weight', 'ASC')
      ->execute();
  }

}
