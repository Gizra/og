<?php

namespace Drupal\og\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when OG permissions are compiled.
 *
 * This event allows implementing modules to provide their own OG permissions or
 * alter existing permissions that are provided by other modules.
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
  protected $entityTypeId;

  /**
   * The bundle ID of the group type to which the permissions apply.
   *
   * @var string
   */
  protected $bundleId;

  /**
   * Constructs a PermissionEvent object.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group type for which the permissions are
   *   collected.
   * @param string $bundle_id
   *   The bundle ID of the group type for which the permissions are collected.
   */
  public function __construct($entity_type_id, $bundle_id) {
    $this->entityTypeId = $entity_type_id;
    $this->bundleId = $bundle_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($name) {
    if (!isset($this->permissions[$name])) {
      throw new \InvalidArgumentException("The '$name' permission does not exist.'");
    }
    return $this->permissions[$name];
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
  public function setPermission($name, array $permission) {
    if (empty($name)) {
      throw new \InvalidArgumentException('Permission name is required.');
    }
    if (empty($permission['title'])) {
      throw new \InvalidArgumentException('The permission title is required.');
    }
    if (!empty($permission['operation']) && (empty($permission['entity type']) || empty($permission['bundle']))) {
      throw new \InvalidArgumentException('When an operation is provided, the entity type and bundle to which this operation applies should also be provided.');
    }
    // Default the ownership to 'any' for operations.
    if (!empty($permission['operation']) && empty($permission['ownership'])) {
      $permission['ownership'] = 'any';
    }
    $this->permissions[$name] = $permission;
  }

  /**
   * {@inheritdoc}
   */
  public function setPermissions(array $permissions) {
    foreach ($permissions as $name => $permission) {
      $this->setPermission($name, $permission);
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
  public function hasPermission($name) {
    return isset($this->permissions[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleId() {
    return $this->bundleId;
  }

  /**
   * {@inheritdoc}
   */
  public function filterByDefaultRole($role_name) {
    return array_filter($this->permissions, function ($permission) use ($role_name) {
      return !empty($permission['default roles']) && in_array($role_name, $permission['default roles']);
    });
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
    $this->setPermission($key, $value);
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
