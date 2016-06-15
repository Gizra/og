<?php

namespace Drupal\og;

/**
 * Interface for OG permission managers.
 */
interface PermissionManagerInterface {

  /**
   * Generates the OG permission list for the given group type.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group for which to generate the permissions.
   * @param string $bundle_id
   *   The bundle ID of the group for which to generate the permissions.
   *
   * @return array
   *   The list of permissions.
   */
  public function getPermissionList($entity_type_id, $bundle_id);

  /**
   * Helper function to generate default crud permissions for a given bundle.
   *
   * @param $group_content_entity_type_id
   *   The entity type ID for which to generate the permission list.
   * @param $group_content_bundle_id
   *   The bundle ID for which to generate the permission list.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  public function generateCrudPermissionList($group_content_entity_type_id, $group_content_bundle_id);

}
