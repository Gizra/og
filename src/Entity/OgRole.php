<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgRole.
 */
namespace Drupal\og\Entity;

use Drupal\Core\Config\ConfigValueException;
use Drupal\og\Exception\OgRoleRequiredException;
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
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
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
   * @var integer
   *
   * The group ID.
   */
  protected $group_id;

  /**
   * The entity type ID of the group.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The bundle ID of the group.
   *
   * @var string
   */
  protected $group_bundle;

  /**
   * Set the ID of the role.
   *
   * @param string $id
   *   The machine name of the role.
   *
   * @return OgRole
   */
  public function setId($id) {
    $this->id = $id;
    $this->set('id', $id);
    return $this;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * @param string $label
   *
   * @return OgRole
   */
  public function setLabel($label) {
    $this->label = $label;
    $this->set('label', $label);
    return $this;
  }

  /**
   * @return int
   */
  public function getGroupID() {
    return $this->get('group_id');
  }

  /**
   * @param int $groupID
   *
   * @return OgRole
   */
  public function setGroupID($groupID) {
    $this->group_id = $groupID;
    $this->set('group_id', $groupID);
    return $this;
  }

  /**
   * @return string
   */
  public function getGroupType() {
    return $this->get('group_type');
  }

  /**
   * @param string $groupType
   *
   * @return OgRole
   */
  public function setGroupType($groupType) {
    $this->group_type = $groupType;
    $this->set('group_type', $groupType);
    return $this;
  }

  /**
   * @return string
   */
  public function getGroupBundle() {
    return $this->get('group_bundle');
  }

  /**
   * @param string $groupBundle
   *
   * @return OgRole
   */
  public function setGroupBundle($groupBundle) {
    $this->group_bundle = $groupBundle;
    $this->set('group_bundle', $groupBundle);
    return $this;
  }

  /**
   * Returns the role type.
   *
   * @return string
   *   The role type. One of OgRoleInterface::ROLE_TYPE_ANONYMOUS,
   *   OgRoleInterface::ROLE_TYPE_AUTHENTICATED or
   *   OgRoleInterface::ROLE_TYPE_STANDARD.
   */
  public function getRoleType() {
    return $this->get('role_type');
  }

  /**
   * Sets the role type.
   *
   * @param string $role_type
   *   The role type to set. One of OgRoleInterface::ROLE_TYPE_ANONYMOUS,
   *   OgRoleInterface::ROLE_TYPE_AUTHENTICATED or
   *   OgRoleInterface::ROLE_TYPE_STANDARD.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid role type is given.
   */
  public function setRoleType($role_type) {
    if (!in_array($role_type, [
      self::ROLE_TYPE_ANONYMOUS,
      self::ROLE_TYPE_AUTHENTICATED,
      self::ROLE_TYPE_STANDARD,
    ])) {
      throw new \InvalidArgumentException("'$role_type' is not a valid role type.");
    }
    return $this->set('role_type', $role_type);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {

    if ($this->isNew()) {

      if (empty($this->group_type)) {
        throw new ConfigValueException('The group type can not be empty.');
      }

      if (empty($this->group_bundle)) {
        throw new ConfigValueException('The group bundle can not be empty.');
      }

      // When assigning a role to group we need to add a prefix to the ID in
      // order to prevent duplicate IDs.
      $prefix = $this->group_type . '-' . $this->group_bundle . '-';

      if (!empty($this->group_id)) {
        $prefix .= $this->group_id . '-';
      }

      $this->id = $prefix . $this->id();
    }

    parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Prevent the ID, role type, entity type or bundle to be changed for any of
    // the default roles. These default roles are required and shouldn't be
    // tampered with.
    $is_locked_property = in_array($property_name, [
      'id',
      'role_type',
      'group_type',
      'group_bundle',
    ]);
    $is_default_role = $this->getRoleType() !== self::ROLE_TYPE_STANDARD;
    if ($is_locked_property && $is_default_role && !$this->isNew()) {
      throw new OgRoleRequiredException("The $property_name of the default roles 'non-member' and 'member' cannot be changed.");
    }
    return parent::set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // The default roles are required. Prevent them from being deleted for as
    // long as the group still exists.
    if (in_array($this->id(), [self::ANONYMOUS, self::AUTHENTICATED]) && $this->groupManager()->isGroup($this->getGroupType(), $this->getGroupBundle())) {
      throw new OgRoleRequiredException('The default roles "non-member" and "member" cannot be deleted.');
    }
    parent::delete();
  }

  /**
   * Returns default properties for each of the standard role names.
   *
   * @param string $role_name
   *   A role name, one of OgRoleInterface::ANONYMOUS,
   *   OgRoleInterface::AUTHENTICATED, OgRoleInterface::ADMINISTRATOR.
   *
   * @return array
   *   An array of default properties, to pass to OgRole::create().
   */
  public static function getDefaultProperties($role_name) {
    if (!in_array($role_name, [
      self::ANONYMOUS,
      self::AUTHENTICATED,
      self::ADMINISTRATOR,
    ])) {
      throw new \InvalidArgumentException("$role_name is not a default role name.");
    }

    $default_properties = [
      self::ANONYMOUS => [
        'role_type' => OgRoleInterface::ROLE_TYPE_ANONYMOUS,
        'label' => 'Non-member',
        'permissions' => ['subscribe'],
      ],
      self::AUTHENTICATED => [
        'role_type' => OgRoleInterface::ROLE_TYPE_AUTHENTICATED,
        'label' => 'Member',
        'permissions' => ['unsubscribe'],
      ],
      self::ADMINISTRATOR => [
        'role_type' => OgRoleInterface::ROLE_TYPE_AUTHENTICATED,
        'label' => 'Administrator',
        'permissions' => [
          'add user',
          'administer group',
          'approve and deny subscription',
          'manage members',
          'manage permissions',
          'manage roles',
          'update group',
        ],
      ],
    ];

    return $default_properties[$role_name];
  }

  /**
   * Gets the group manager.
   *
   * @return \Drupal\og\GroupManager
   *   The group manager.
   */
  protected function groupManager() {
    // Returning the group manager by calling the global factory method might
    // seem less than ideal, but Entity classes are not designed to work with
    // proper dependency injection. The ::create() method only accepts a $values
    // array, which is not compatible with ContainerInjectionInterface.
    // See for example Entity::uuidGenerator() in the base Entity class, it
    // also uses this pattern.
    return \Drupal::service('og.group.manager');
  }

}
