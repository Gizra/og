<?php

namespace Drupal\og\Event;

use Drupal\og\PermissionInterface;

/**
 * Interface for PermissionEvent classes.
 *
 * This event allows implementing modules to provide their own OG permissions or
 * alter existing permissions that are provided by other modules.
 */
interface PermissionEventInterface extends \ArrayAccess, \IteratorAggregate {

  /**
   * The event name.
   */
  const EVENT_NAME = 'og.permission';

  /**
   * Returns the permission with the given name.
   *
   * @param string $name
   *   The name of the permission to return.
   *
   * @return \Drupal\og\PermissionInterface
   *   The permission.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the permission with the given name does not exist.
   */
  public function getPermission($name);

  /**
   * Returns a group content operation permission by its identifying properties.
   *
   * @param string $entity_type_id
   *   The group content entity type ID to which this permission applies.
   * @param string $bundle_id
   *   The group content bundle ID to which this permission applies.
   * @param string $operation
   *   The entity operation to which this permission applies.
   * @param bool $owner
   *   Set to FALSE if this permission applies to all entities, or to TRUE if it
   *   only applies to the ones owned by the user. Defaults to FALSE.
   *
   * @return \Drupal\og\GroupContentOperationPermission
   *   The permission.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the permission with the given properties does not exist.
   */
  public function getGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $owner = FALSE);

  /**
   * Returns all the permissions.
   *
   * @return \Drupal\og\PermissionInterface[]
   *   An associative array of permissions, keyed by permission name.
   */
  public function getPermissions();

  /**
   * Sets the permission with the given data.
   *
   * @param \Drupal\og\PermissionInterface $permission
   *   The permission to set.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the permission has no name or title.
   */
  public function setPermission(PermissionInterface $permission);

  /**
   * Sets multiple permissions.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   The permissions to set, keyed by permission name.
   */
  public function setPermissions(array $permissions);

  /**
   * Deletes the given permission.
   *
   * @param string $name
   *   The name of the permission to delete.
   */
  public function deletePermission($name);

  /**
   * Deletes a group content operation permission by its identifying properties.
   *
   * @param string $entity_type_id
   *   The group content entity type ID to which this permission applies.
   * @param string $bundle_id
   *   The group content bundle ID to which this permission applies.
   * @param string $operation
   *   The entity operation to which this permission applies.
   * @param bool $owner
   *   Set to FALSE if this permission applies to all entities, or to TRUE if it
   *   only applies to the ones owned by the user. Defaults to FALSE.
   */
  public function deleteGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $owner = FALSE);

  /**
   * Returns whether or not the given permission exists.
   *
   * @param string $name
   *   The name of the permission for which to verify the existance.
   *
   * @return bool
   *   TRUE if the permission exists, FALSE otherwise.
   */
  public function hasPermission($name);

  /**
   * Returns if a group content operation permission matches given properties.
   *
   * @param string $entity_type_id
   *   The group content entity type ID to which this permission applies.
   * @param string $bundle_id
   *   The group content bundle ID to which this permission applies.
   * @param string $operation
   *   The entity operation to which this permission applies.
   * @param bool $owner
   *   Set to FALSE if this permission applies to all entities, or to TRUE if it
   *   only applies to the ones owned by the user. Defaults to FALSE.
   *
   * @return bool
   *   Whether or not the permission exists.
   */
  public function hasGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $owner = FALSE);

  /**
   * Returns the entity type ID of the group to which the permissions apply.
   *
   * @return string
   *   The entity type ID.
   */
  public function getGroupEntityTypeId();

  /**
   * Returns the bundle ID of the group to which the permissions apply.
   *
   * @return string
   *   The bundle ID.
   */
  public function getGroupBundleId();

  /**
   * Returns the IDs of group content bundles to which the permissions apply.
   *
   * @return array
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   */
  public function getGroupContentBundleIds();

}
