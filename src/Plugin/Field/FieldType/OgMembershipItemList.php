<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipItemList.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\og\Controller\OG;
use Drupal\og\Entity\OgMembership;

/**
 * Defines an item list class for OG membership fields.
 */
class OgMembershipItemList extends EntityReferenceFieldItemList {

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
   *
   * @todo Need to account for new items added and not being overridden etc..
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

    // Automatically create the first item for computed fields.
    if ($index == 0 && !isset($this->list[0])) {
      $this->list[0] = $this->createItem(0);
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
      ->condition('etid', $this->getEntity()->id())
      ->condition('entity_type', $this->getEntity()->getEntityTypeId())
      ->condition('group_type', $this->getFieldDefinition()->getTargetEntityTypeId())
      ->condition('field_name', $this->getFieldDefinition()->getName())
      ->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = OgMembership::loadMultiple($membership_ids);

    $target_group_ids = array_map(function($membership) {
      return $membership->getGid();
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
      $storage = \Drupal::entityManager()->getStorage('og_membership');
      // Use array_keys() as the values will contain the group ID.
      $entities = $storage->loadMultiple(array_keys($deprecated_membership_ids));
      $storage->delete($entities);
    }
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
    return array_map(function($item) {
      return $item->entity;
    }, $this->list);
  }

  /**
   * Populate reference items for active group memberships.
   */
  protected function populateGroupsFromMembershipEntities() {
    // Make sure list is clear.
    $this->list = [];
    $entity = $this->getEntity();
    $group_type = $this->getFieldDefinition()->getSetting('target_type');

    $membership_ids = \Drupal::entityQuery('og_membership')
      ->condition('field_name', $this->getName())
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('etid', $entity->id())
      ->condition('group_type', $group_type)
      ->condition('state', OG_STATE_ACTIVE)
      ->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = OgMembership::loadMultiple($membership_ids);

    $group_ids = array_map(function ($membership) {
      return $membership->getGid();
    }, $memberships);

    $groups = \Drupal::entityManager()->getStorage($group_type)->loadMultiple($group_ids);

    $delta = 0;
    foreach ($groups as $group) {
      $this->list[$delta] = $this->createItem($delta, ['entity' => $group]);
      $delta++;
    }
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
    $membership = OG::MembershipStorage()->create(OG::MembershipDefault());

    $membership
      ->setFieldName($this->getName())
      ->setEntityType($parent_entity->getEntityTypeId())
      ->setEntityId($parent_entity->id())
      ->setGroupType($this->getFieldDefinition()->getTargetEntityTypeId())
      ->setGid($group_id)
      ->save();

    return $membership;
  }

}
