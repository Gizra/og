<?php

namespace Drupal\og;

/**
 * Interface for OG permission managers.
 */
interface PermissionManagerInterface {

  /**
   * Returns the list of entity operation permissions for a given group content.
   *
   * These are permissions such as 'edit own article content'.
   *
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   *
   * @return array
   *   The list of permissions.
   */
  public function getEntityOperationPermissions(array $group_content_bundle_ids);

  /**
   * Helper method to generate entity operation permissions for a given bundle.
   *
   * @param $group_content_entity_type_id
   *   The entity type ID for which to generate the permission list.
   * @param $group_content_bundle_id
   *   The bundle ID for which to generate the permission list.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  public function generateEntityOperationPermissionList($group_content_entity_type_id, $group_content_bundle_id);

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
