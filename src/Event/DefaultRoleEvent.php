<?php

namespace Drupal\og\Event;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The OG Role entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogRoleStorage;

  /**
   * Constructs a DefaultRoleEvent object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->ogRoleStorage = $entity_type_manager->getStorage('og_role');
  }

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
  public function addRole(array $properties) {
    $this->validate($properties);

    if (array_key_exists($properties['name'], $this->roles)) {
      throw new \InvalidArgumentException("The '{$properties['name']}' role already exists.");
    }

    // Provide default values.
    $properties += [
      'role_type' => OgRoleInterface::ROLE_TYPE_STANDARD,
      'is_admin' => FALSE,
    ];

    $this->roles[$properties['name']] = $this->ogRoleStorage->create($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function addRoles(array $roles) {
    foreach ($roles as $role => $properties) {
      $this->addRole($properties);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRole(array $properties) {
    $this->validate($properties);
    $this->deleteRole($properties['name']);
    $this->addRole($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function setRoles(array $roles) {
    foreach ($roles as $name => $properties) {
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
  public function offsetSet($key, $value) {
    $this->validate($value);
    if ($value['name'] !== $key) {
      throw new \InvalidArgumentException('The key and the "name" property of the role should be identical.');
    }
    $this->setRole($value);
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
   * {@inheritdoc}
   */
  public function reset() {
    $this->roles = [];
  }

  /**
   * Validates a role that is about to be set or added.
   *
   * @param array $properties
   *   The role properties to validate.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the role name is empty, the 'label' property is missing, or
   *   the 'role_type' property is invalid.
   */
  protected function validate($properties) {
    if (empty($properties['name'])) {
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
