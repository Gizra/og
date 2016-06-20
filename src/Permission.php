<?php

namespace Drupal\og;

/**
 * Base class for OG permissions.
 */
abstract class Permission {

  /**
   * The name of the permission.
   *
   * @var string
   */
  protected $name;

  /**
   * The human readable permission title.
   *
   * @var string
   */
  protected $title;

  /**
   * A short description of the permission.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The default roles to which this permission applies.
   *
   * @var array
   */
  protected $defaultRoles = [];

  /**
   * If the permission is security sensitive and should be limited to admins.
   *
   * @var bool
   */
  protected $restrictAccess = FALSE;

  /**
   * Constructs a Permission object.
   *
   * @param array $values
   *   An associative array of values, keyed by property.
   */
  public function __construct(array $values = []) {
    foreach ($values as $property => $value) {
      $this->set($property, $value);
    }
  }

  /**
   * Sets the value for the given property.
   *
   * @param string $property
   *   The name of the property to set.
   * @param mixed $value
   *   The value to set.
   */
  protected function set($property, $value) {
    $property = $this->lowerCamelize($property);
    $this->validate($property, $value);
    $this->$property = $value;
  }

  /**
   * Returns the value for the given property.
   *
   * @param string $property
   *   The property to return.
   *
   * @return mixed
   *   The value.
   */
  protected function get($property) {
    $property = $this->lowerCamelize($property);
    if (!property_exists($this, $property)) {
      throw new \InvalidArgumentException("Invalid property $property.");
    }
    return $this->$property;
  }

  /**
   * Returns the machine name of the permission.
   *
   * @return string
   *   The permission machine name.
   */
  public function getName() {
    return $this->get('name');
  }

  /**
   * Sets the machine name of the permission.
   *
   * @param string $name
   *   The machine name.
   *
   * @return $this
   */
  public function setName($name) {
    $this->set('name' , $name);
    return $this;
  }

  /**
   * Returns the human readable permission title.
   *
   * @return string
   *   The human readable permission title.
   */
  public function getTitle() {
    return $this->get('title');
  }

  /**
   * Sets the human readable permission title.
   *
   * @param string $title
   *   The human readable title.
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->set('title' , $title);
    return $this;
  }

  /**
   * Returns the description.
   *
   * @return string
   *   The description.
   */
  public function getDescription() {
    return $this->get('description');
  }

  /**
   * Sets the description.
   *
   * @param string $description
   *   The machine description.
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->set('description' , $description);
    return $this;
  }

  /**
   * Returns the default roles.
   *
   * @return array
   *   The default roles.
   */
  public function getDefaultRoles() {
    return $this->get('default roles');
  }

  /**
   * Sets the default roles.
   *
   * @param array $default_roles
   *   The default roles.
   *
   * @return $this
   */
  public function setDefaultRoles(array $default_roles) {
    $this->set('default roles' , $default_roles);
    return $this;
  }

  /**
   * Returns whether or not access is restricted.
   *
   * @return bool
   *   Whether or not access is restricted.
   */
  public function getRestrictAccess() {
    return $this->get('restrict access');
  }

  /**
   * Sets the access restriction.
   *
   * @param bool $access
   *   Whether or not this permission is security sensitive and should only be
   *   granted to administrators.
   *
   * @return $this
   */
  public function setRestrictAccess($access) {
    $this->set('restrict access' , $access);
    return $this;
  }

  /**
   * Validates the given property and value.
   *
   * @param string $property
   *   The property to validate.
   * @param mixed $value
   *   The value to validate.
   */
  public function validate($property, $value) {
    if (!property_exists($this, $property)) {
      throw new \InvalidArgumentException("Invalid property $property.");
    }

    if ($property === 'restrictAccess' && !is_bool($value)) {
      throw new \InvalidArgumentException('The value for the "restrict access" property should be a boolean.');
    }
  }

  /**
   * Converts the given string in a lowerCamelCase version.
   *
   * @param string $string
   *   The string to convert.
   *
   * @return string
   *   The converted string.
   */
  protected static function lowerCamelize($string) {
    return lcfirst(str_replace(' ', '', ucwords($string)));
  }

}
