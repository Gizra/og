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
  public function generateEntityOperationPermissionList($group_content_entity_type_id, $group_content_bundle_id);

}
