<?php

namespace Drupal\dgi_members;

use Drupal\node\NodeInterface;

/**
 * Member operations service interface.
 */
interface DgiMembersEntityOperationsInterface {

  /**
   * Confirm the routeMatch node parameter has the 'Compound Object' term.
   *
   * @return bool
   *   TRUE if the current node in the route is a compound object, FALSE
   *   otherwise.
   */
  public function nodeFromRouteIsCompound() : bool;

  /**
   * Retrieve the first member of the given object or the node from url param.
   *
   * @return false|\Drupal\node\NodeInterface
   *   FALSE if unable to retrieve an active member, or the member if present.
   */
  public function retrieveActiveMember($url_param = NULL) : FALSE|NodeInterface;

  /**
   * Retrieve the first member of the contextual 'Node'.
   *
   * @return false|\Drupal\node\NodeInterface
   *   FALSE if the member could not be retrieved, or the member object.
   */
  public function retrieveFirstOfMembers() : FALSE|NodeInterface;

  /**
   * Retrieve 'members' of the current page object.
   *
   * @return false|string[]|int[]
   *   An array of member IDs for the given object, or FALSE if none found.
   */
  public function membersQueryExecute() : array|FALSE;

}
