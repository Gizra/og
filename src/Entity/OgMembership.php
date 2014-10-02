<?php

/**
 * Contains Drupal\og\Entity\OgMembership.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;

/**
 * The OG membership is the main idea behind OG. The OG membership entity keep
 * the connection between the group and the her content. For example we have the
 * node 1 which is a group and the node 2 which is node that belong to a group:
 * @code:
 *  $membership = OgMembership::create(array('type' => 'og_membership_type_default'));
 *  $membership
 *    ->setContentId(2)
 *    ->setContentType('node')
 *    ->setGid(1)
 *    ->setEntityType('node')
 *    ->setFieldName(OG_AUDIENCE_FIELD)
 *    ->save();
 * @endcode
 *
 * Although the reference stored in the base table og_membership, there is a
 * need for an easy way the group and the group content content via the UI. This
 * is where the entity reference field come in: The field tables in the DB are
 * empty, but when asking the content of the field there a is work behind the
 * scene that structured the field value's on the fly. That's one of OG magic.
 *
 * @ContentEntityType(
 *   id = "og_membership",
 *   label = @Translation("OG membership"),
 *   bundle_label = @Translation("OG membership type"),
 *   module = "og",
 *   base_table = "og_membership",
 *   fieldable = TRUE,
 *   bundle_entity_type = "og_membership_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *   },
 *   bundle_keys = {
 *     "bundle" = "type"
 *   }
 * )
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
   * @return OgMembership.
   */
  public function setCreated($created) {
    $this->set('created', $created);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * @param mixed $entityType
   *
   * @return OgMembership.
   */
  public function setEntityType($entityType) {
    $this->set('entity_type', $entityType);
    return $this;
  }

  /**
   * @return mixed
   *
   * todo: The method collide with getEntityType method.
   */
  public function _getEntityType() {
    return $this->get('entityType')->value;
  }

  /**
   * @param mixed $etid
   *
   * @return OgMembership
   */
  public function setContentId($etid) {
    $this->set('etid', $etid);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getContentId() {
    return $this->get('etid')->value;
  }

  /**
   * @param mixed $fieldName
   *
   * @return OgMembership.
   */
  public function setFieldName($fieldName) {
    $this->set('field_name', $fieldName);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getFieldName() {
    return $this->get('field_name')->value;
  }

  /**
   * @param mixed $gid
   *
   * @return OgMembership.
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
   * @return OgMembership
   */
  public function setContentType($groupType) {
    $this->set('group_type', $groupType);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getContentType() {
    return $this->get('group_type')->value;
  }

  /**
   * @param mixed $id
   *
   * @return OgMembership.
   */
  public function setId($id) {
    $this->set('id', $id);
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
   * @return OgMembership.
   */
  public function setLanguage($language) {
    $this->set('language', $language);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getLanguage() {
    return $this->get('language')->value;
  }

  /**
   * @param mixed $state
   *
   * @return OgMembership.
   */
  public function setState($state) {
    $this->set('state', $state);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * @param mixed $type
   *
   * @return OgMembership.
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t("The group membership's unique ID."))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The bundle of the membership'))
      ->setSetting('target_type', 'og_membership_type');

    $fields['etid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID.'));

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t("The entity type (e.g. node, comment, etc')."));

    $fields['group_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID.'));

    $fields['gid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group ID.'))
      ->setDescription(t("The group's entity type (e.g. node, comment, etc')."));

    $fields['state'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('State'))
      ->setDescription(t("The state of the group content."));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Create'))
      ->setDescription(t('The Unix timestamp when the group content was created.'));

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field name'))
      ->setDescription(t("The name of the field holding the group ID, the OG membership is associated with."));

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The {languages}.language of this membership.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function PreSave(EntityStorageInterface $storage) {

    if (!$this->getFieldName()) {
      $this->setFieldName(OG_AUDIENCE_FIELD);
    }

    parent::PreSave($storage);
  }

  /**
   * Get the group object.
   *
   * @return EntityInterface
   */
  public function getGroup() {
    return entity_load($this->getGroupType(), $this->getGid());
  }

  /**
   * Get the entity belong to the current membership.
   *
   * @return EntityInterface
   */
  public function getEntityMembership() {
    return entity_load($this->get('entity_type'), $this->getEtid());
  }
}
