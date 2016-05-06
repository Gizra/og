<?php

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
   * Set the ID of the role.
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
   * {@inheritdoc}
   */
  public function save() {
    if ($this->isNew()) {
      if (empty($this->getGroupType())) {
        throw new ConfigValueException('The group type can not be empty.');
      }

      if (empty($this->getGroupBundle())) {
        throw new ConfigValueException('The group bundle can not be empty.');
      }

      // When assigning a role to group we need to add a prefix to the ID in
      // order to prevent duplicate IDs.
      $prefix = $this->getGroupType() . '-' . $this->getGroupBundle() . '-';

      if (!empty($this->getGroupId())) {
        $prefix .= $this->getGroupId() . '-';
      }

      $this->id = $prefix . $this->id();
    }

    parent::save();
  }

}
