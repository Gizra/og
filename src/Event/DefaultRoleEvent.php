<?php

namespace Drupal\og\Event;

use Drupal\og\Entity\OgRole;
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
  public function addRole(OgRole $role) {
    $this->validate($role);

    if (array_key_exists($role->getName(), $this->roles)) {
      throw new \InvalidArgumentException("The '{$role->getName()}' role already exists.");
    }

    $this->roles[$role->getName()] = $role;
  }

  /**
   * {@inheritdoc}
   */
  public function addRoles(array $roles) {
    foreach ($roles as $role) {
      $this->addRole($role);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRole(OgRole $role) {
    $this->validate($role);
    $this->deleteRole($role->getName());
    $this->addRole($role);
  }

  /**
   * {@inheritdoc}
   */
  public function setRoles(array $roles) {
    foreach ($roles as $properties) {
      $this->setRole($properties);
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
  public function offsetSet($key, $role) {
    $this->validate($role);
    if ($role->getName() !== $key) {
      throw new \InvalidArgumentException('The key and the "name" property of the role should be identical.');
    }
    $this->setRole($role);
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
   * Validates that a role that is about to be set or added has a name.
   *
   * The roles are stored locally keyed by role name.
   *
   * @param \Drupal\og\Entity\OgRole $role
   *   The role to validate.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the role name is empty.
   */
  protected function validate(OgRole $role) {
    if (empty($role->getName())) {
      throw new \InvalidArgumentException('Role name is required.');
    }
  }

}
