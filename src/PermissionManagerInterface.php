<?php

namespace Drupal\og;

/**
 * Interface for OG permission managers.
 */
interface PermissionManagerInterface {

  /**
   * Returns the full set of default permissions for a group and its content.
   *
   * This returns both group level permissions such as 'subscribe without
   * approval' and group content entity operation permissions such as 'edit own
   * article content'.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group for which to return permissions.
   * @param string $group_bundle_id
   *   The bundle ID of the group for which to return permissions.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   * @param string $role_name
   *   Optional default role name to filter the permissions on. If omitted, all
   *   permissions will be returned.
   *
   * @return \Drupal\og\PermissionInterface[]
   *   The array of permissions.
   */
  public function getDefaultPermissions($group_entity_type_id, $group_bundle_id, array $group_content_bundle_ids, $role_name = NULL);

  /**
   * Returns permissions that are enabled by default for the given role.
   *
   * This returns group level permissions such as 'subscribe without approval'
   * and 'administer group'.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group for which to return permissions.
   * @param string $group_bundle_id
   *   The bundle ID of the group for which to return permissions.
   * @param string $role_name
   *   Optional default role name to filter the permissions on. If omitted, all
   *   permissions will be returned.
   *
   * @return \Drupal\og\GroupPermission[]
   *   An array of permissions that are enabled by default for the given role.
   */
  public function getDefaultGroupPermissions($group_entity_type_id, $group_bundle_id, $role_name = NULL);

  /**
   * Returns the list of entity operation permissions for a given group content.
   *
   * This returns group content entity operation permissions such as 'edit own
   * article content'.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group for which to return permissions.
   * @param string $group_bundle_id
   *   The bundle ID of the group for which to return permissions.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   * @param string $role_name
   *   Optional default role name to filter the permissions on. If omitted, all
   *   permissions will be returned.
   *
   * @return \Drupal\og\GroupContentOperationPermission[]
   *   The array of permissions.
   */
  public function getDefaultEntityOperationPermissions($group_entity_type_id, $group_bundle_id, array $group_content_bundle_ids, $role_name = NULL);

}
