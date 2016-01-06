<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgRole.
 */
namespace Drupal\og\Entity;

use Drupal\Core\Config\ConfigValueException;
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
 *     "permissions"
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
}
