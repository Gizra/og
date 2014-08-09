<?php

/**
 * Contain the OG role entity definition. This will be a content entity.
 */
namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 */
class OgRole extends ContentEntityBase {

  /**
   * @param mixed $gid
   *
   * @return $this
   */
  public function setGid($gid) {
    $this->gid = $gid;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getGid() {
    return $this->gid;
  }

  /**
   * @param mixed $groupBundle
   *
   * @return $this
   */
  public function setGroupBundle($groupBundle) {
    $this->groupBundle = $groupBundle;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getGroupBundle() {
    return $this->groupBundle;
  }

  /**
   * @param mixed $groupType
   *
   * @return $this
   */
  public function setGroupType($groupType) {
    $this->groupType = $groupType;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getGroupType()  {
    return $this->groupType;
  }

  /**
   * @param mixed $name
   *
   * @return $this
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $rid
   *
   * @return $this
   */
  public function setRid($rid) {
    $this->rid = $rid;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getRid() {
    return $this->rid;
  }

  /**
   * @var Integer
   *
   * The identifier.
   */
  protected $rid;

  /**
   * @var Integer
   *
   * The group ID.
   */
  protected $gid;

  /**
   * @var String
   *
   * The group group's entity type.
   */
  protected $groupType;

  /**
   * @var String
   *
   * The group's bundle name.
   */
  protected $groupBundle;

  /**
   * @var String
   *
   * Unique role name per group.
   */
  protected $name;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['rid'] = FieldDefinition::create('integer')
      ->setLabel(t('Role ID'))
      ->setDescription(t('Primary Key: Unique role ID.'));

    $fields['gid'] = FieldDefinition::create('integer')
      ->setLabel(t('Group ID'))
      ->setDescription(t("The group's unique ID."));

    $fields['group_type'] = FieldDefinition::create('string')
      ->setLabel(t('Group type'))
      ->setDescription(t("The group's entity type."));

    $fields['group_bundle'] = FieldDefinition::create('string')
      ->setLabel(t('Group bundle'))
      ->setDescription(t("The group's bundle name."));

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Unique role name per group.'));

    return $fields;
  }
}