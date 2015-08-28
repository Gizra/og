<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipItemList.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;

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

    $delta = 0;
    foreach ($groups as $group) {
      $this->list[] = $this->createItem($delta, ['entity' => $group]);
      $delta++;
    }
  }

}
