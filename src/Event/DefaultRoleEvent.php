<?php

namespace Drupal\og\Event;

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
    return $this->roles;
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
   *   Thrown when the role name is empty, or a required property is missing.
   */
  protected function validate($name, $properties) {
    if (empty($name)) {
      throw new \InvalidArgumentException('Role name is required.');
    }
    if (empty($properties['label'])) {
      throw new \InvalidArgumentException('The label property is required.');
    }
  }

}
