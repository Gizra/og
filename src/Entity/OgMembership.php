<?php

/**
 * @file
 * Contains Drupal\og\Entity\OgMembership.
 */

namespace Drupal\og\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;

/**
 * The OG membership is the main idea behind OG. The OG membership entity keep
 * the connection between the group and the her content. For example we have the
 * node 1 which is a group and the node 2 which is node that belong to a group:
 * @code:
 *  $membership = OgMembership::create(['type' => \Drupal\og\OgMembershipInterface::TYPE_DEFAULT]);
 *  $membership
 *    ->setContentId(2)
 *    ->setContentType('node')
 *    ->setGid(1)
 *    ->setEntityType('node')
 *    ->setFieldName(OgGroupAudienceHelper::DEFAULT_FIELD)
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
class OgMembership extends ContentEntityBase implements OgMembershipInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser($etid) {
    $this->set('uid', $etid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldName($fieldName) {
    $this->set('field_name', $fieldName);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->get('field_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityId($gid) {
    $this->set('entity_id', $gid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($groupType) {
    $this->set('entity_type', $groupType);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupEntityType() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    $this->set('state', $state);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setRoles($role_ids) {
    $this->set('roles', $role_ids);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($role_id) {
    $rids = $this->getRolesIds();
    $rids[] = $role_id;

    return $this->setRoles(array_unique($rids));
  }

  /**
   * {@inheritdoc}
   */
  public function revokeRole($role_id) {
    $rids = $this->getRolesIds();
    $key = array_search($role_id, $rids);
    unset($rids[$key]);

    return $this->setRoles(array_unique($rids));
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->get('roles')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesIds() {
    return array_map(function (OgRole $role) {
      return $role->id();
    }, $this->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return array_filter($this->getRoles(), function (OgRole $role) use ($permission) {
      return $role->hasPermission($permission);
    });
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

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The membership UUID.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The bundle of the membership'))
      ->setSetting('target_type', 'og_membership_type');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member entity ID'))
      ->setDescription(t('The entity ID of the member.'))
      ->setTargetEntityTypeId('user');

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity type'))
      ->setDescription(t('The entity type of the group.'));

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity id.'))
      ->setDescription(t("The entity ID of the group."));

    $fields['state'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('State'))
      ->setDescription(t("The state of the group content."))
      ->setDefaultValue(TRUE);

    $fields['roles'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Roles'))
      ->setDescription(t('The OG roles related to an OG membership entity.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'og_role');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Create'))
      ->setDescription(t('The Unix timestamp when the group content was created.'));

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field name'))
      ->setDescription(t("The name of the field holding the group ID, the OG membership is associated with."))
      ->setDefaultValue(OgGroupAudienceHelper::DEFAULT_FIELD);

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
      $this->setFieldName(OgGroupAudienceHelper::DEFAULT_FIELD);
    }

    parent::PreSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return \Drupal::entityTypeManager()->getStorage($this->getGroupEntityType())->load($this->getEntityId());
  }

}
