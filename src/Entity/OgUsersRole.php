<?php

/**
 * Contain the OG role entity definition. This will be a content entity.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\user\Entity\User;

/**
 */
class OgUsersRole extends ContentEntityBase implements ContentEntityInterface {

  /**
   * @var User
   *
   * The user object.
   */
  protected $uid;

  /**
   * @var OgRole
   *
   * The role entity.
   */
  protected $rid;

  /**
   * @var Integer
   *
   * The group id.
   */
  protected $gid;

  /**
   * @var String
   *
   * The group type.
   */
  protected $groupType;

  /**
   * @param mixed $gid
   *
   * @return $this
   */
  public function setGid($gid) {
    $this->set('gid', $gid);

    return $this;
  }

  /**
   * @return mixed
   */
  public function getGid() {
    return $this->get('gid')->value;
  }

  /**
   * @param mixed $groupType
   *
   * @return $this
   */
  public function setGroupType($groupType) {
    $this->set('group_type', $groupType);

    return $this;
  }

  /**
   * @return mixed
   */
  public function getGroupType() {
    return $this->get('group_type')->value;
  }

  /**
   * @param mixed $rid
   *
   * @return $this
   */
  public function setRid($rid) {
    $this->set('rid', $rid);

    return $this;
  }

  /**
   * @return mixed
   */
  public function getRid() {
    return $this->get('rid')->value;
  }

  /**
   * @param mixed $uid
   *
   * @return $this
   */
  public function setUid($uid) {
    $this->set('uid', $uid);

    return $this;
  }

  /**
   * @return mixed
   */
  public function getUid() {
    return $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['uid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t("The user's role object."))
      ->setSetting('target_type', 'user');

    $fields['rid'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('OG role'))
      ->setDescription(t('The OG role entity.'))
      ->setSetting('target_type', 'og_role');

    $fields['gid'] = FieldDefinition::create('integer')
      ->setLabel(t('Group ID'))
      ->setDescription(t("The group's unique ID."));

    $fields['group_type'] = FieldDefinition::create('string')
      ->setLabel(t('Group type'))
      ->setDescription(t("The group's entity type."));

    return $fields;
  }
}