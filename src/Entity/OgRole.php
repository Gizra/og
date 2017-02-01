<?php

namespace Drupal\og\Entity;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\og\Exception\OgRoleException;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\Role;

/**
 * Defines the OG user role entity class.
 *
 * @see \Drupal\user\Entity\Role
 *
 * @ConfigEntityType(
 *   id = "og_role",
 *   label = @Translation("OG role"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\og_ui\Form\OgRoleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/group/role/{og_role}/delete",
 *     "edit-form" = "/admin/config/group/role/{og_role}/edit",
 *     "edit-permissions-form" = "/admin/config/group/permission/{og_role}/edit",
 *     "collection" = "/admin/config/group/roles",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "is_admin",
 *     "group_type",
 *     "group_bundle",
 *     "group_id",
 *     "permissions",
 *     "role_type"
 *   }
 * )
 */
class OgRole extends Role implements OgRoleInterface {

  /**
   * The role name.
   *
   * @var string
   */
  protected $name;

  /**
   * Constructs an OgRole object.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   */
  public function __construct(array $values) {
    parent::__construct($values, 'og_role');
  }

  /**
   * Sets the ID of the role.
   *
   * @param string $id
   *   The machine name of the role.
   *
   * @return $this
   */
  public function setId($id) {
    $this->set('id', $id);
    return $this;
  }

  /**
   * Returns the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * Sets the label.
   *
   * @param string $label
   *   The label to set.
   *
   * @return $this
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * Returns the group ID.
   *
   * @return int
   *   The group ID.
   */
  public function getGroupId() {
    return $this->get('group_id');
  }

  /**
   * Sets the group ID.
   *
   * @param int $group_id
   *   The group ID to set.
   *
   * @return $this
   */
  public function setGroupId($group_id) {
    $this->set('group_id', $group_id);
    return $this;
  }

  /**
   * Returns the group type.
   *
   * @return string
   *   The group type.
   */
  public function getGroupType() {
    return $this->get('group_type');
  }

  /**
   * Sets the group type.
   *
   * @param string $group_type
   *   The group type to set.
   *
   * @return $this
   */
  public function setGroupType($group_type) {
    $this->set('group_type', $group_type);
    return $this;
  }

  /**
   * Returns the group bundle.
   *
   * @return string
   *   The group bundle.
   */
  public function getGroupBundle() {
    return $this->get('group_bundle');
  }

  /**
   * Sets the group bundle.
   *
   * @param string $group_bundle
   *   The group bundle to set.
   *
   * @return $this
   */
  public function setGroupBundle($group_bundle) {
    $this->set('group_bundle', $group_bundle);
    return $this;
  }

  /**
   * Returns the role type.
   *
   * @return string
   *   The role type. One of OgRoleInterface::ROLE_TYPE_REQUIRED or
   *   OgRoleInterface::ROLE_TYPE_STANDARD.
   */
  public function getRoleType() {
    return $this->get('role_type') ?: OgRoleInterface::ROLE_TYPE_STANDARD;
  }

  /**
   * Sets the role type.
   *
   * @param string $role_type
   *   The role type to set. One of OgRoleInterface::ROLE_TYPE_REQUIRED or
   *   OgRoleInterface::ROLE_TYPE_STANDARD.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid role type is given.
   */
  public function setRoleType($role_type) {
    if (!in_array($role_type, [
      self::ROLE_TYPE_REQUIRED,
      self::ROLE_TYPE_STANDARD,
    ])) {
      throw new \InvalidArgumentException("'$role_type' is not a valid role type.");
    }
    return $this->set('role_type', $role_type);
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return $this->get('role_type') !== OgRoleInterface::ROLE_TYPE_STANDARD;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    // If the name is not set yet, try to derive it from the ID.
    if (empty($this->name) && $this->id() && $this->getGroupType() && $this->getGroupBundle()) {
      // Check if the ID matches the pattern '{entity type}-{bundle}-{name}'.
      $pattern = preg_quote("{$this->getGroupType()}-{$this->getGroupBundle()}-");
      preg_match("/$pattern(.+)/", $this->id(), $matches);
      if (!empty($matches[1])) {
        $this->setName($matches[1]);
      }
    }
    return $this->get('name');
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByGroupAndName(EntityInterface $group, $name) {
    $role_id = "{$group->getEntityTypeId()}-{$group->bundle()}-$name";
    return self::load($role_id);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // The ID of a new OgRole has to consist of the entity type ID, bundle ID
    // and role name, separated by dashes.
    if ($this->isNew() && $this->id()) {
      $pattern = preg_quote("{$this->getGroupType()}-{$this->getGroupBundle()}-{$this->getName()}");
      if (!preg_match("/$pattern/", $this->id())) {
        throw new ConfigValueException('The ID should consist of the group entity type ID, group bundle ID and role name, separated by dashes.');
      }
    }

    // If a new OgRole is saved and the ID is not set, construct the ID from
    // the entity type ID, bundle ID and role name.
    if ($this->isNew() && !$this->id()) {
      if (!$this->getGroupType()) {
        throw new ConfigValueException('The group type can not be empty.');
      }

      if (!$this->getGroupBundle()) {
        throw new ConfigValueException('The group bundle can not be empty.');
      }

      if (!$this->getName()) {
        throw new ConfigValueException('The role name can not be empty.');
      }

      // When assigning a role to group we need to add a prefix to the ID in
      // order to prevent duplicate IDs.
      $prefix = $this->getGroupType() . '-' . $this->getGroupBundle() . '-';

      if ($this->getGroupId()) {
        $prefix .= $this->getGroupId() . '-';
      }

      $this->setId($prefix . $this->getName());
    }

    // Reset access cache, as the role might have changed.
    $this->ogAccess()->reset();

    parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Prevent the ID, role type, group ID, group entity type or bundle from
    // being changed once they are set. These properties are required and
    // shouldn't be tampered with.
    $is_locked_property = in_array($property_name, [
      'id',
      'role_type',
      'group_id',
      'group_type',
      'group_bundle',
    ]);

    if (!$is_locked_property || $this->isNew()) {
      return parent::set($property_name, $value);
    }

    if ($this->get($property_name) == $value) {
      // Locked property hasn't changed, so we can return early.
      return $this;
    }

    throw new OgRoleException("The $property_name cannot be changed.");
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // The default roles are required. Prevent them from being deleted for as
    // long as the group still exists.
    if (in_array($this->id(), [self::ANONYMOUS, self::AUTHENTICATED]) && $this->groupTypeManager()->isGroup($this->getGroupType(), $this->getGroupBundle())) {
      throw new OgRoleException('The default roles "non-member" and "member" cannot be deleted.');
    }

    // Reset access cache, as the role is no longer present.
    $this->ogAccess()->reset();
    parent::delete();
  }

  /**
   * Maps role names to role types.
   *
   * The 'anonymous' and 'authenticated' roles should not be changed or deleted.
   * All others are standard roles.
   *
   * @param string $role_name
   *   The role name for which to return the type.
   *
   * @return string
   *   The role type, either OgRoleInterface::ROLE_TYPE_REQUIRED or
   *   OgRoleInterface::ROLE_TYPE_STANDARD.
   */
  public static function getRoleTypeByName($role_name) {
    return in_array($role_name, [
      OgRoleInterface::ANONYMOUS,
      OgRoleInterface::AUTHENTICATED,
    ]) ? OgRoleInterface::ROLE_TYPE_REQUIRED : OgRoleInterface::ROLE_TYPE_STANDARD;
  }

  /**
   * {@inheritdoc}
   */
  public static function getRole($entity_type_id, $bundle, $role_name) {
    return self::load($entity_type_id . '-' . $bundle . '-' . $role_name);
  }

  /**
   * Gets the group manager.
   *
   * @return \Drupal\og\GroupTypeManager
   *   The group manager.
   */
  protected function groupTypeManager() {
    // Returning the group manager by calling the global factory method might
    // seem less than ideal, but Entity classes are not designed to work with
    // proper dependency injection. The ::create() method only accepts a $values
    // array, which is not compatible with ContainerInjectionInterface.
    // See for example Entity::uuidGenerator() in the base Entity class, it
    // also uses this pattern.
    return \Drupal::service('og.group_type_manager');
  }

  /**
   * Gets the OG access service.
   *
   * @return \Drupal\og\OgAccessInterface
   *   The OG access service.
   */
  protected function ogAccess() {
    return \Drupal::service('og.access');
  }

}
