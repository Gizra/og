<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipItemList.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\og\Controller\OG;
use Drupal\og\Entity\OgMembership;

/**
 * Defines a item list class for OG membership fields.
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
   * @param $property_name
   *
   * @return mixed
   */
  public function __get($property_name) {
    // For empty fields, $entity->field->property is NULL.
    if ($item = $this->first()) {
      return $item->__get($property_name);
    }
  }

  public function postSave($update) {
    parent::postSave($update);

    $new_groups = [];
    foreach ($this->list as $item) {
      $new_groups[] = $item->getValue()['target_id'];
    }

    // todo: move to API function.
    $results = \Drupal::entityQuery('og_membership')
      ->condition('etid', $this->getEntity()->id())
      ->condition('entity_type', $this->getEntity()->getEntityTypeId())
      ->condition('group_type', $this->getFieldDefinition()->getTargetEntityTypeId())
      ->condition('field_name', $this->getFieldDefinition()->getName())
      ->execute();

    /** @var OgMembership[] $memberships */
    $memberships = OgMembership::loadMultiple($results);

    $target_ids = [];
    if ($memberships) {
      // Collect all the previous memberships into array.
      foreach ($memberships as $membership) {
        $target_ids[] = $membership->getGroup()->id();
      }
    }

    $deprecated_memberships = [];
    // Iterate over the new groups and create/delete a membership according to
    // the foreach inside logic.
    foreach ($new_groups as $new_group) {
      if (!$target_ids) {
        // This is an orphan group content - group it to all groups.
        $this->createOgMembership($new_group);
        continue;
      }

      if (!in_array($new_group, $target_ids)) {
        // The membership does not exists in the new groups array. Delete it.
        $deprecated_memberships[] = $new_group;
      }
      else {
        // We need to create a new membership.
        $this->createOgMembership($new_group);
      }
    }

    if ($deprecated_memberships) {
      $storage_handler = \Drupal::entityManager()->getStorage('og_membership');
      $entities = OgMembership::loadMultiple($deprecated_memberships);
      $storage_handler->delete($entities);
    }
  }

  private function createOgMembership($id) {
    /** @var \Drupal\Core\Entity\EntityInterface $parent */
    $parent_entity = $this->getEntity();
    $membership = OG::MembershipStorage()->create(OG::MembershipDefault());

    $membership
      ->setFieldName($this->getName())
      ->setEntityType($parent_entity->getEntityTypeId())
      ->setEntityId($parent_entity->id())
      ->setGroupType($this->getFieldDefinition()->getTargetEntityTypeId())
      ->setGid($id)
      ->setFieldName($this->getFieldDefinition()->getName())
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {

    // todo: Fix this one in order to get all the group the entity belong t0.

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
   * Populate the list property with groups based on related memberships.
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
      ->execute();

    $memberships = \Drupal::entityManager()->getStorage('og_membership')->loadMultiple($membership_ids);

    $group_ids = array_map(function ($membership) {
      return $membership->getGid();
    }, $memberships);

    $groups = \Drupal::entityManager()->getStorage($group_type)->loadMultiple($group_ids);

    foreach ($groups as $delta => $group) {
      $this->list[] = $this->createItem($delta, ['entity' => $group]);
    }
  }

}
