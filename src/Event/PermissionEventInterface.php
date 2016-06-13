<?php

namespace Drupal\og\Event;

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
   * @return array
   *   An associative array, keyed by permission name, with the following keys:
   *   - 'title': The human readable permission title. Make sure this is
   *     translated with t().
   *   - 'description': Optional longer description to show alongside the
   *     permission. Make sure this is translated with t().
   *   - 'roles': Optional array of default role names to which the permission
   *     is limited. For example the 'subscribe' permission can only be assigned
   *     to non-members, as a member doesn't need it. Values can be one or more
   *     of OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED, or
   *     OgRoleInterface::ADMINISTRATOR.
   *   - 'default roles': Optional array of default role names for which the
   *     permission will be enabled by default. Values can be one or more of
   *     OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED, or
   *     OgRoleInterface::ADMINISTRATOR.
   *   - 'restrict access': Optional boolean indicating whether or not this is a
   *     permission that requires elevated privileges. Use this for permissions
   *     that are mainly intended for the group administrator or similar roles.
   *     Defaults to FALSE.
   *   - 'operation': Optional operation that is applicable to this permission
   *     in case this is about granting access to an entity operation. Example:
   *     if the permission was 'create any article content', the operation would
   *     be 'create'.
   *   - 'entity type': @todo
   *   - 'bundle': @todo
   *   - 'ownership': @todo
   *
   * @throws \InvalidArgumentException
   *   Thrown when the permission with the given name does not exist.
   */
  public function getPermission($name);

  /**
   * Returns all the permissions.
   *
   * @return array
   *   An associative array of permission arrays, keyed by permission name.
   */
  public function getPermissions();

  /**
   * Sets the permission with the given data.
   *
   * @param string $name
   *   The name of the permission to set.
   * @param array $permission
   *   The permission array to set.
   *
   * @throws \InvalidArgumentException
   *   Thrown when no name is given, or when the permission array does not have
   *   a title key.
   */
  public function setPermission($name, array $permission);

  /**
   * Sets multiple permissions.
   *
   * @param array $permissions
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
   * Returns the entity type ID of the group to which the permissions apply.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Returns the bundle ID of the group to which the permissions apply.
   *
   * @return string
   *   The bundle ID.
   */
  public function getBundleId();

  /**
   * Returns permissions that are enabled by default for the given role.
   *
   * @param string $role_name
   *   A default role name. One of OgRoleInterface::ANONYMOUS,
   *   OgRoleInterface::AUTHENTICATED, or OgRoleInterface::ADMINISTRATOR.
   *
   * @return array
   *   An array of permissions that are enabled by default for the given role.
   */
  public function filterByDefaultRole($role_name);

}
