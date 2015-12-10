<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgRole.
 */
namespace Drupal\og\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

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
 *     "weight" = "weight",
 *     "group_type" = "groupType",
 *     "group_bundle" = "groupBundle",
 *     "uid" = "uid",
 *     "permissions" = "permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "group_type",
 *     "group_bundle",
 *     "uid",
 *     "permissions"
 *   }
 * )
 */
class OgRole extends ConfigEntityBase implements OgRoleInterface {

  /**
   * The machine name of this role.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of this role.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of this role in administrative listings.
   *
   * @var int
   */
  protected $weight;

  /**
   * @var integer
   *
   * The group ID.
   */
  protected $groupID;

  /**
   * The entity type ID of the group.
   *
   * @var string
   */
  protected $groupType;

  /**
   * The bundle ID of the group.
   *
   * @var string
   */
  protected $groupBundle;


  /**
   * The permissions belonging to this role.
   *
   * @var array
   */
  protected $permissions = array();

  /**
   * An indicator whether the role has all permissions.
   *
   * @var bool
   */
  protected $is_admin;

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
    $this->groupID = $groupID;
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
    $this->groupType = $groupType;
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
    $this->groupBundle = $groupBundle;
    $this->set('group_bundle', $groupBundle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    if ($this->isAdmin()) {
      return [];
    }
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    if ($this->isAdmin()) {
      return TRUE;
    }
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    if ($this->isAdmin()) {
      return $this;
    }
    if (!$this->hasPermission($permission)) {
      $this->permissions[] = $permission;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    if ($this->isAdmin()) {
      return $this;
    }
    $this->permissions = array_diff($this->permissions, array($permission));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdmin() {
    return (bool) $this->is_admin;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsAdmin($is_admin) {
    $this->is_admin = $is_admin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!isset($this->weight) && ($roles = $storage->loadMultiple())) {
      // Set a role weight to make this new role last.
      $max = array_reduce($roles, function($max, $role) {
        return $max > $role->weight ? $max : $role->weight;
      });
      $this->weight = $max + 1;
    }
  }

}
