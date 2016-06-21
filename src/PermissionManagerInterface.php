<?php

namespace Drupal\og;

/**
 * Interface for OG permission managers.
 */
interface PermissionManagerInterface {

  /**
   * Returns permissions that are enabled by default for the given role.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group for which to return permissions.
   * @param string $group_bundle_id
   *   The bundle ID of the group for which to return permissions.
   * @param string $role_name
   *   Optional default role name to filter the permissions on. If omitted, all
   *   permissions will be returned.
   *
   * @return array
   *   An array of permissions that are enabled by default for the given role.
   */
  public function getDefaultPermissions($group_entity_type_id, $group_bundle_id, $role_name = NULL);

}
