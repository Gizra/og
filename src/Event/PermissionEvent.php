<?php

namespace Drupal\og\Event;

use Drupal\og\GroupContentOperationPermission;
use Drupal\og\PermissionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when OG permissions are compiled.
 *
 * This event allows implementing modules to provide their own OG permissions or
 * alter existing permissions that are provided by other modules.
 *
 * The entity types and bundles of both the group and the related group content
 * are available and can be used to determine the applicable permissions.
 */
class PermissionEvent extends Event implements PermissionEventInterface {

  /**
   * The list of permissions.
   *
   * @var array
   *   Associative array of permission arrays, keyed by permission name.
   */
  protected $permissions = [];

  /**
   * The entity type ID of the group type to which the permissions apply.
   *
   * @var string
   */
  protected $groupEntityTypeId;

  /**
   * The bundle ID of the group type to which the permissions apply.
   *
   * @var string
   */
  protected $groupBundleId;

  /**
   * The bundle IDs of the group content types to which the permissions apply.
   *
   * @var array
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   */
  protected $groupContentBundleIds;

  /**
   * Constructs a PermissionEvent object.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group type for which the permissions are
   *   collected.
   * @param string $group_bundle_id
   *   The bundle ID of the group type for which the permissions are collected.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   */
  public function __construct($group_entity_type_id, $group_bundle_id, $group_content_bundle_ids) {
    $this->groupEntityTypeId = $group_entity_type_id;
    $this->groupBundleId = $group_bundle_id;
    $this->groupContentBundleIds = $group_content_bundle_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($name) {
    if (!isset($this->permissions[$name])) {
      throw new \InvalidArgumentException("The '$name' permission does not exist.");
    }
    return $this->permissions[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $ownership = 'any') {
    foreach ($this->getPermissions() as $permission) {
      if (
        $permission instanceof GroupContentOperationPermission
        && $permission->getEntityType() === $entity_type_id
        && $permission->getBundle() === $bundle_id
        && $permission->getOperation() === $operation
        && $permission->getOwnership() === $ownership
      ) {
        return $permission;
      }
    }

    throw new \InvalidArgumentException('The permission with the given properties does not exist.');
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function setPermission(PermissionInterface $permission) {
    if (empty($permission->getName())) {
      throw new \InvalidArgumentException('Permission name is required.');
    }
    if (empty($permission->getTitle())) {
      throw new \InvalidArgumentException('The permission title is required.');
    }
    $this->permissions[$permission->getName()] = $permission;
  }

  /**
   * {@inheritdoc}
   */
  public function setPermissions(array $permissions) {
    foreach ($permissions as $permission) {
      $this->setPermission($permission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePermission($name) {
    if ($this->hasPermission($name)) {
      unset($this->permissions[$name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $ownership = 'any') {
    $permission = $this->getGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $ownership);
    $this->deletePermission($permission->getName());
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($name) {
    return isset($this->permissions[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $ownership = 'any') {
    try {
      $this->getGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $ownership);
    }
    catch (\InvalidArgumentException $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupEntityTypeId() {
    return $this->groupEntityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupBundleId() {
    return $this->groupBundleId;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContentBundleIds() {
    return $this->groupContentBundleIds;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($key) {
    return $this->getPermission($key);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($key, $value) {
    if (!$value instanceof PermissionInterface) {
      throw new \InvalidArgumentException('The value must be an object of type PermissionInterface.');
    }
    if ($value->getName() !== $key) {
      throw new \InvalidArgumentException('The key and the permission name must be identical.');
    }
    $this->setpermission($value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($key) {
    if ($this->hasPermission($key)) {
      $this->deletePermission($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($key) {
    return $this->hasPermission($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->permissions);
  }

}
