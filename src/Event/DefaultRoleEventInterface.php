<?php

namespace Drupal\og\Event;

use Drupal\og\Entity\OgRole;

/**
 * Interface for DefaultRoleEvent classes.
 *
 * This event allows implementing modules to provide their own default roles or
 * alter existing default roles that are provided by other modules.
 */
interface DefaultRoleEventInterface extends \ArrayAccess, \IteratorAggregate {

  /**
   * The event name.
   */
  const EVENT_NAME = 'og.default_role';

  /**
   * Returns a single default role.
   *
   * @param string $name
   *   The name of the role to return.
   *
   * @return \Drupal\og\Entity\OgRole
   *   The OgRole entity. Note that we cannot specify OgRoleInterface here
   *   because of limitations in interface inheritance in PHP 5.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the role with the given name does not exist.
   */
  public function getRole($name);

  /**
   * Returns all the default role names.
   *
   * @return array
   *   An associative array of default role properties, keyed by role name.
   */
  public function getRoles();

  /**
   * Adds a default role.
   *
   * @param \Drupal\og\Entity\OgRole $role
   *   The OgRole entity to add. This should be an unsaved entity that doesn't
   *   have the group entity type and bundle IDs set.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the role that is added already exists.
   */
  public function addRole(OgRole $role);

  /**
   * Adds multiple default roles.
   *
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   An array of OgRole entities to add. These should be unsaved entities that
   *   don't have the group entity type and bundle IDs set.
   */
  public function addRoles(array $roles);

  /**
   * Sets a default roles.
   *
   * @param \Drupal\og\Entity\OgRole $role
   *   The OgRole entity to set. This should be an unsaved entity that doesn't
   *   have the group entity type and bundle IDs set.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the role name is empty, or when the 'label' property is
   *   missing.
   */
  public function setRole(OgRole $role);

  /**
   * Sets multiple default roles.
   *
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   An array of OgRole entities to set. These should be unsaved entities that
   *   don't have the group entity type and bundle IDs set.
   */
  public function setRoles(array $roles);

  /**
   * Deletes the given default role.
   *
   * @param string $name
   *   The name of the role to delete.
   */
  public function deleteRole($name);

  /**
   * Returns whether or not the given role exists.
   *
   * @param string $name
   *   The name of the role for which to verify the existence.
   *
   * @return bool
   *   TRUE if the role exists, FALSE otherwise.
   */
  public function hasRole($name);

}
