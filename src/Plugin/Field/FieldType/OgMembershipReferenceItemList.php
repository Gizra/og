<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItemList.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\og\Og;
use Drupal\og\Entity\OgMembership;
use Drupal\og\OgMembershipInterface;

/**
 * Defines an item list class for OG membership fields.
 */
class OgMembershipReferenceItemList extends EntityReferenceFieldItemList {

  /**
   * Whether or not this list of field items has fetched membership groups.
   *
   * @var bool
   */
  protected $fetched = FALSE;

  /**
   * {@inheritdoc}
   */
  public function count() {
    // Overrides ListItem::count, implementing \Countable::count
    // Fetch the list of memberships for this field.
    if (!$this->fetched) {
      $this->populateGroupsFromMembershipEntities();
      $this->fetched = TRUE;
    }

    return parent::count();
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    if (!is_numeric($index)) {
      throw new \InvalidArgumentException('Unable to get a value with a non-numeric delta in a list.');
    }

    // Fetch the list of memberships for this field.
    if (!$this->fetched) {
      $this->populateGroupsFromMembershipEntities();
      $this->fetched = TRUE;
    }

    // Just return an empty item if the first item is requested and the list is
    // empty. Storing this in the list would lead to an incorrect count.
    if ($index == 0 && !isset($this->list[0])) {
      return $this->createItem(0);
    }

    return isset($this->list[$index]) ? $this->list[$index] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    $group_ids = [];
    foreach ($this->list as $item) {
      $group_ids[] = $item->getValue()['target_id'];
    }

    // todo: move to API function.
    $membership_ids = \Drupal::entityQuery('og_membership')
      ->condition('member_entity_id', $this->getEntity()->id())
      ->condition('member_entity_type', $this->getEntity()->getEntityTypeId())
      ->condition('group_entity_type', $this->getFieldDefinition()->getTargetEntityTypeId())
      ->condition('field_name', $this->getFieldDefinition()->getName())
      ->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = OgMembership::loadMultiple($membership_ids);

    $target_group_ids = array_map(function(OgMembership $membership) {
      return $membership->getGroupEntityid();
    }, $memberships);

    $deprecated_membership_ids = array_diff($target_group_ids, $group_ids);
    $new_membership_group_ids = array_diff($group_ids, $target_group_ids);

    // Create any new memberships.
    foreach ($new_membership_group_ids as $new_membership_group_id) {
      // We need to create a new membership.
      $this->createOgMembership($new_membership_group_id);
    }

    // Remove memberships that are not referenced any more.
    if ($deprecated_membership_ids) {
      $storage = \Drupal::entityTypeManager()->getStorage('og_membership');
      // Use array_keys() as the values will contain the group ID.
      $entities = $storage->loadMultiple(array_keys($deprecated_membership_ids));
      $storage->delete($entities);
    }

    // Set fetched to FALSE to force a rebuild of the membership group data if
    // it is used after saving.
    $this->fetched = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    // Fetch the list of memberships for this field.
    if (!$this->fetched) {
      $this->populateGroupsFromMembershipEntities();
      $this->fetched = TRUE;
    }

    if (empty($this->list)) {
      return [];
    }

    // Entities should be populated from populateGroupsFromMembershipEntities().
    return array_filter(array_map(function($item) {
      return $item->entity;
    }, $this->list));
  }

  /**
   * Populate reference items for active group memberships.
   */
  protected function populateGroupsFromMembershipEntities() {
    // Save the current list.
    $old_list = [];
    foreach ($this->list as $item) {
      $old_list[$item->target_id] = $item;
    }
    // Make sure list is clear.
    $this->list = [];
    $entity = $this->getEntity();
    $group_type = $this->getFieldDefinition()->getSetting('target_type');

    $membership_ids = \Drupal::entityQuery('og_membership')
      ->condition('field_name', $this->getName())
      ->condition('member_entity_type', $entity->getEntityTypeId())
      ->condition('member_entity_id', $entity->id())
      ->condition('group_entity_type', $group_type)
      ->condition('state', OgMembershipInterface::STATE_ACTIVE)
      ->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = OgMembership::loadMultiple($membership_ids);

    $group_ids = array_map(function (OgMembership $membership) {
      return $membership->getGroupEntityid();
    }, $memberships);

    $groups = \Drupal::entityTypeManager()->getStorage($group_type)->loadMultiple($group_ids);

    $new_list = [];
    foreach ($groups as $group_id => $group) {
      // Avoid duplicates.
      unset($old_list[$group_id]);
      $new_list[] = $this->createItem(count($new_list), ['entity' => $group]);
    }
    $this->list = array_merge($old_list, $new_list);
  }

  /**
   * Creates and saves a new membership.
   *
   * @param int|string $group_id
   *   The group ID to create a membership for.
   */
  protected function createOgMembership($group_id) {
    /** @var \Drupal\Core\Entity\EntityInterface $parent */
    $parent_entity = $this->getEntity();
    /** @var OgMembership $membership */
    $membership = Og::membershipStorage()->create(Og::membershipDefault());

    $membership
      ->setFieldName($this->getName())
      ->setMemberEntityType($parent_entity->getEntityTypeId())
      ->setMemberEntityId($parent_entity->id())
      ->setGroupEntityType($this->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type'))
      ->setGroupEntityid($group_id)
      ->save();

    return $membership;
  }

}
