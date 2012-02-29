<?php

/**
 * OG behavior handler.
 */
class OgBehaviorHandler extends EntityReference_BehaviorHandler_Abstract {

  public function access($field, $instance) {
    return $field['settings']['handler'] == 'og';
  }

  public function load($entity_type, $entities, $field, $instances, $langcode, &$items) {
    // Get the OG memberships from the field.
    foreach ($entities as $entity) {
      $wrapper = entity_metadata_wrapper($entity_type, $entity);
      if (empty($wrapper->{$field['field_name'] . '__og_membership'})) {
        // If the entity belongs to a bundle that was deleted, return early.
        continue;
      }
      $id = $wrapper->getIdentifier();
      foreach ($wrapper->{$field['field_name'] . '__og_membership'}->value() as $og_membership) {
        $items[$id][] = array(
          'target_id' => $og_membership->gid,
        );
      }
    }
  }

  public function insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
    $this->OgMembershipCrud($entity_type, $entity, $field, $instance, $langcode, $items);
    $items = array();
  }

  public function update($entity_type, $entity, $field, $instance, $langcode, &$items) {
    $this->OgMembershipCrud($entity_type, $entity, $field, $instance, $langcode, $items);
    $items = array();
  }

  public function delete($entity_type, $entity, $field, $instance, $langcode, &$items) {
    $this->OgMembershipCrud($entity_type, $entity, $field, $instance, $langcode, $items);
  }

  /**
   * Create, update or delete OG membership based on field values.
   */
  public function OgMembershipCrud($entity_type, $entity, $field, $instance, $langcode, &$items) {
    $diff = $this->groupAudiencegetDiff($entity_type, $entity, $field, $instance, $langcode, $items);
    if (!$diff) {
      return;
    }

    $field_name = $field['field_name'];
    $group_type = $field['settings']['target_type'];

    $diff += array('insert' => array(), 'delete' => array());

    // Delete first, so we don't trigger cardinality errors.
    if ($diff['delete']) {
      og_membership_delete_multiple($diff['delete']);
    }

    foreach ($diff['insert'] as $gid) {
      $values = array(
        'entity_type' => $entity_type,
        'entity' => $entity,
        'field_name' => $field_name,
      );

      og_group($group_type, $gid, $values);
    }
  }

  /**
   * Get the difference in group audience for a saved field.
   *
   * @return
   *   Array with all the differences, or an empty array if none found.
   */
  public function groupAudiencegetDiff($entity_type, $entity, $field, $instance, $langcode, $items) {
    $return = FALSE;

    $field_name = $field['field_name'];
    $wrapper = entity_metadata_wrapper($entity_type, $entity);
    $og_memberships = $wrapper->{$field_name . '__og_membership'}->value();

    $new_memberships = array();
    foreach ($items as $item) {
      $new_memberships[$item['target_id']] = TRUE;
    }

    foreach ($og_memberships as $og_membership) {
      $gid = $og_membership->gid;
      if (empty($new_memberships[$gid])) {
        // Membership was deleted.
        if ($og_membership->entity_type == 'user') {
          // Make sure this is not the group manager, if exists.
          $group = entity_load_single($og_membership->group_type, $og_membership->gid);
          if (!empty($group->uid) && $group->uid == $og_membership->etid) {
            continue;
          }
        }

        $return['delete'][] = $og_membership->id;
        unset($new_memberships[$gid]);
      }
      else {
        // Existing membership.
        unset($new_memberships[$gid]);
      }
    }
    if ($new_memberships) {
      // New memberships.
      $return['insert'] = array_keys($new_memberships);
    }

    return $return;
  }
}
