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
   * Whether or not the parent entity we depend on is being removed.
   *
   * @var bool
   *   TRUE if the entity is being removed.
   */
  protected $parentEntityIsBeingRemoved = FALSE;

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
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->set('id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->set('label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return $this->get('group_type');
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupType($group_type) {
    $this->set('group_type', $group_type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupBundle() {
    return $this->get('group_bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupBundle($group_bundle) {
    $this->set('group_bundle', $group_bundle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleType() {
    return $this->get('role_type') ?: OgRoleInterface::ROLE_TYPE_STANDARD;
  }

  /**
   * {@inheritdoc}
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
  public static function loadByGroupType($group_entity_type_id, $group_bundle_id) {
    $properties = [
      'group_type' => $group_entity_type_id,
      'group_bundle' => $group_bundle_id,
    ];
    return \Drupal::entityTypeManager()->getStorage('og_role')->loadByProperties($properties);
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
    // long as the group still exists, unless the group itself is in the process
    // of being removed.
    if (!$this->parentEntityIsBeingRemoved && $this->isRequired() && $this->groupTypeManager()->isGroup($this->getGroupType(), $this->getGroupBundle())) {
      throw new OgRoleException('The default roles "non-member" and "member" cannot be deleted.');
    }

    // Reset access cache, as the role is no longer present.
    $this->ogAccess()->reset();
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return static::getRoleTypeByName($this->getName()) === OgRoleInterface::ROLE_TYPE_REQUIRED;
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
   * @return \Drupal\og\GroupTypeManagerInterface
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

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Create a dependency on the group bundle.
    $bundle_config_dependency = \Drupal::entityTypeManager()->getDefinition($this->getGroupType())->getBundleConfigDependency($this->getGroupBundle());
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // The parent entity we depend on is being removed. Set a flag so we can
    // allow removal of required roles.
    $this->parentEntityIsBeingRemoved = TRUE;
    return parent::onDependencyRemoval($dependencies);
  }

}
