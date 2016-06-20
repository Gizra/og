<?php

namespace Drupal\og;

/**
 * Interface for OG permission classes.
 */
interface PermissionInterface {

  /**
   * Returns the value for the given property.
   *
   * @param string $property
   *   The property to return.
   *
   * @return mixed
   *   The value.
   */
  public function get($property);

  /**
   * Sets the value for the given property.
   *
   * @param string $property
   *   The name of the property to set.
   * @param mixed $value
   *   The value to set.
   */
  public function set($property, $value);

  /**
   * Returns the machine name of the permission.
   *
   * @return string
   *   The permission machine name.
   */
  public function getName();

  /**
   * Sets the machine name of the permission.
   *
   * @param string $name
   *   The machine name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Returns the human readable permission title.
   *
   * @return string
   *   The human readable permission title.
   */
  public function getTitle();

  /**
   * Sets the human readable permission title.
   *
   * @param string $title
   *   The human readable title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Returns the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

  /**
   * Sets the description.
   *
   * @param string $description
   *   The machine description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the default roles.
   *
   * @return array
   *   The default roles.
   */
  public function getDefaultRoles();

  /**
   * Sets the default roles.
   *
   * @param array $default_roles
   *   The default roles.
   *
   * @return $this
   */
  public function setDefaultRoles(array $default_roles);

  /**
   * Returns whether or not access is restricted.
   *
   * @return bool
   *   Whether or not access is restricted.
   */
  public function getRestrictAccess();

  /**
   * Sets the access restriction.
   *
   * @param bool $access
   *   Whether or not this permission is security sensitive and should only be
   *   granted to administrators.
   *
   * @return $this
   */
  public function setRestrictAccess($access);

}
