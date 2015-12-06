<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgRole.
 */
namespace Drupal\og\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * @ConfigEntityType(
 *   id = "og_role",
 *   label = @Translation("OG role"),
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "group_type" = "groupType",
 *     "group_bundle" = "groupBundle",
 *     "uid" = "uid",
 *     "permissions" = "permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "group_type",
 *     "group_bundle",
 *     "uid",
 *     "permissions"
 *   }
 * )
 */
class OgRole extends ConfigEntityBase {

  /**
   * @var integer
   *
   * The identifier of the role.
   */
  protected $id;

  /**
   * @var string
   *
   * The label of the role.
   */
  protected $label;

  /**
   * @var integer
   *
   * The group ID.
   */
  protected $groupID;

  /**
   * @var string
   *
   * The group type. i.e: node, user
   */
  protected $groupType;

  /**
   * @var string
   *
   * The group bundle. i.e: article, page
   */
  protected $groupBundle;

  /**
   * @var integer
   *
   * The user ID which the role assign to.
   */
  protected $uid;

  /**
   * @var array
   *
   * List of permissions.
   */
  protected $permissions;

  /**
   * @return int
   */
  public function getId() {
    return $this->get('id');
  }

  /**
   * @param int $id
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
   * @return int
   */
  public function getUid() {
    return $this->get('uid');
  }

  /**
   * @param int $uid
   *
   * @return OgRole
   */
  public function setUid($uid) {
    $this->uid = $uid;
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * @return array
   */
  public function getPermissions() {
    return $this->get('permissions');;
  }

  /**
   * @param array $permissions
   *
   * @return OgRole
   */
  public function setPermissions($permissions) {
    $this->permissions = $permissions;
    $this->set('permissions', $permissions);
    return $this;
  }

}
