<?php

/**
 * Contain the OG membership entity definition. This will be a content entity.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Language\Language;

/**
 */
class OgMembership extends ContentEntityBase implements ContentEntityInterface {

  /**
   * @var Integer
   *
   * The identifier of the row.
   */
  protected $id;

  /**
   * @var OgMembershipType
   *
   * The membership type of the current membership instance.
   */
  protected $type;

  /**
   * @var Integer
   *
   * The entity ID.
   */
  protected $etid;

  /**
   * @var String
   *
   * The entity type.
   */
  protected $entityType;

  /**
   * @var Integer
   *
   * The group ID.
   */
  protected $gid;

  /**
   * @var String
   *
   * The group type.
   */
  protected $groupType;

  /**
   * @var Integer
   *
   * The state of the membership.
   */
  protected $state;

  /**
   * @var Integer
   *
   * The unix time stamp the membership was created.
   */
  protected $created;

  /**
   * @var String
   *
   * The name of the field holding the group ID, the OG membership is associated
   * with.
   */
  protected $fieldName;

  /**
   * @var Language
   *
   * The language of the membership.
   */
  protected $language;

  /**
   * @param mixed $created
   *
   * @return $this.
   */
  public function setCreated($created) {
    $this->created = $created;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * @param mixed $entityType
   *
   * @return $this.
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * @param mixed $etid
   *
   * @return $this.
   */
  public function setEtid($etid) {
    $this->etid = $etid;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getEtid() {
    return $this->etid;
  }

  /**
   * @param mixed $fieldName
   *
   * @return $this.
   */
  public function setFieldName($fieldName) {
    $this->fieldName = $fieldName;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * @param mixed $gid
   *
   * @return $this.
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
   * @param mixed $groupType
   *
   * @return $this.
   */
  public function setGroupType($groupType) {
    $this->groupType = $groupType;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getGroupType() {
    return $this->groupType;
  }

  /**
   * @param mixed $id
   *
   * @return $this.
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param mixed $language
   *
   * @return $this.
   */
  public function setLanguage($language) {
    $this->language = $language;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getLanguage() {
    return $this->language;
  }

  /**
   * @param mixed $state
   *
   * @return $this.
   */
  public function setState($state) {
    $this->state = $state;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getState() {
    return $this->state;
  }

  /**
   * @param mixed $type
   *
   * @return $this.
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['id'] = FieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t("The group membership's unique ID."))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The bundle of the membership'))
      ->setSetting('target_type', 'og_membership_type')
      ->setSetting('default_value', 0);

    $fields['etid'] = FieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID.'));

    $fields['entity_type'] = FieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t("The entity type (e.g. node, comment, etc')."));

    $fields['gid'] = FieldDefinition::create('integer')
      ->setLabel(t('Group ID.'))
      ->setDescription(t("The group's entity type (e.g. node, comment, etc')."));

    $fields['state'] = FieldDefinition::create('integer')
      ->setLabel(t('State'))
      ->setDescription(t("The state of the group content."));

    $fields['created'] = FieldDefinition::create('created')
      ->setLabel(t('Create'))
      ->setDescription(t('The Unix timestamp when the group content was created.'));

    $fields['field_name'] = FieldDefinition::create('string')
      ->setLabel(t('Field name'))
      ->setDescription(t("The name of the field holding the group ID, the OG membership is associated with."));

    $fields['language'] = FieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The {languages}.language of this membership.'));

    return $fields;
  }
}