<?php

declare(strict_types = 1);

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
   * @param string|null $role_name
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
   * This returns the group level permissions that are populated by default when
   * a new group is created. For example the 'manage members' permission is
   * granted by default to the administrator role, and the 'subscribe'
   * permission to the anonymous role.
   *
   * New default permissions can be added by creating an event listener for the
   * PermissionEvent. The default permissions that ship with Organic Groups can
   * be found in OgEventSubscriber::provideDefaultOgPermissions().
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group for which to return permissions.
   * @param string $group_bundle_id
   *   The bundle ID of the group for which to return permissions.
   * @param string|null $role_name
   *   Optional default role name to filter the permissions on. If omitted, all
   *   permissions will be returned.
   *
   * @return \Drupal\og\GroupPermission[]
   *   An array of permissions that are enabled by default for the given role.
   *
   * @see \Drupal\og\Event\PermissionEventInterface
   * @see \Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultOgPermissions()
   */
  public function getDefaultGroupPermissions($group_entity_type_id, $group_bundle_id, $role_name = NULL);

  /**
   * Returns the list of entity operation permissions for a given group content.
   *
   * This returns group content entity operation permissions such as 'edit own
   * article content'.
   *
   * New default group content entity operation permissions can be added by
   * creating an event listener for the PermissionEvent. The default group
   * content operation permissions that ship with Organic Groups can be found in
   * OgEventSubscriber.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group for which to return permissions.
   * @param string $group_bundle_id
   *   The bundle ID of the group for which to return permissions.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   * @param string|null $role_name
   *   Optional default role name to filter the permissions on. If omitted, all
   *   permissions will be returned.
   *
   * @return \Drupal\og\GroupContentOperationPermission[]
   *   The array of permissions.
   *
   * @see \Drupal\og\Event\PermissionEventInterface
   * @see \Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultNodePermissions()
   * @see \Drupal\og\EventSubscriber\OgEventSubscriber::getDefaultEntityOperationPermissions()
   */
  public function getDefaultEntityOperationPermissions($group_entity_type_id, $group_bundle_id, array $group_content_bundle_ids, $role_name = NULL);

}
