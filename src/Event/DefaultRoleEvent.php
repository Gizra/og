<?php

namespace Drupal\og\Event;

use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when default roles are compiled.
 *
 * This event allows implementing modules to provide their own default roles or
 * alter existing default roles that are provided by other modules.
 */
class DefaultRoleEvent extends Event implements DefaultRoleEventInterface {

  /**
   * The list of default roles.
   *
   * @var array
   *   An associative array of default role properties, keyed by role name.
   */
  protected $roles = [];

  /**
   * {@inheritdoc}
   */
  public function getRole($name) {
    if (!isset($this->roles[$name])) {
      throw new \InvalidArgumentException("The '$name' role does not exist.'");
    }
    return $this->roles[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($name, array $properties) {
    if (array_key_exists($name, $this->roles)) {
      throw new \InvalidArgumentException("The '$name' role already exists.");
    }
    $this->validate($name, $properties);

    // Provide default value for the role type.
    if (empty($properties['role_type'])) {
      $properties['role_type'] = OgRoleInterface::ROLE_TYPE_STANDARD;
    }

    $this->roles[$name] = $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addRoles(array $roles) {
    foreach ($roles as $role => $properties) {
      $this->addRole($role, $properties);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRole($name, array $properties) {
    $this->deleteRole($name);
    $this->addRole($name, $properties);
  }

  /**
   * {@inheritdoc}
   */
  public function setRoles(array $roles) {
    foreach ($roles as $name => $properties) {
      $this->setRole($name, $properties);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRole($name) {
    unset($this->roles[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasRole($name) {
    return isset($this->roles[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($key) {
    return $this->getRole($key);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($key, $value) {
    $this->setRole($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($key) {
    $this->deleteRole($key);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($key) {
    return $this->hasRole($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->roles);
  }

  /**
   * Validates a role that is about to be set or added.
   *
   * @param string $name
   *   The name of the role to add or set.
   * @param array $properties
   *   The role properties to validate.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the role name is empty, the 'label' property is missing, or
   *   the 'role_type' property is invalid.
   */
  protected function validate($name, $properties) {
    if (empty($name)) {
      throw new \InvalidArgumentException('Role name is required.');
    }

    if (empty($properties['label'])) {
      throw new \InvalidArgumentException('The label property is required.');
    }

    $valid_role_types = [
      OgRoleInterface::ROLE_TYPE_STANDARD,
      OgRoleInterface::ROLE_TYPE_REQUIRED,
    ];
    if (!empty($properties['role_type']) && !in_array($properties['role_type'], $valid_role_types)) {
      throw new \InvalidArgumentException('The role type is invalid.');
    }
  }

}
