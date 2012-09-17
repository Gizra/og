<?php

/**
 * @file
 * Import Organic group memberships.
 */

/**
 * Destination class implementing migration into an OG Audience field.
 */
class MigrateDestinationOGMembership extends MigrateDestination {

  public function __toString() {
    return t('og-membership');
  }

  static public function getKeySchema() {
    return array(
      'id' => array(
        'description' => "The group membership's unique ID.",
        'type' => 'int',
        'not null' => TRUE,
      ),
    );
  }

  /**
   * Import a single membership.
   *
   * @param $entity
   *  Object object to build. Prefilled with any fields mapped in the Migration.
   * @param $row
   *  Raw source data object - passed through to prepare/complete handlers.
   * @return array
   *  Array of key fields of the object that was saved if
   *  successful. FALSE on failure.
   */
  public function import(stdClass $entity, stdClass $row) {
    if (empty($entity->group_type)) {
      throw new MigrateException('Missing group type.');
    }
    if (empty($entity->gid)) {
      throw new MigrateException('Missing group ID.');
    }
    if (empty($entity->entity_type)) {
      throw new MigrateException('Missing enity type.');
    }
    if (empty($entity->etid)) {
      throw new MigrateException('Missing enity ID.');
    }

     // Set state, make sure legacy states from OG6 are converted to
     // pending.
    if (isset($entity->state)) {
      $state = $entity->state ? OG_STATE_ACTIVE : OG_STATE_PENDING;
    }
    else {
      $state = OG_STATE_ACTIVE;
    }

    $values = array(
      'entity_type' => $entity->entity_type,
      'entity' => $entity->etid,
      'state' => $state,
      'created' => isset($entity->created) ? $entity->created : REQUEST_TIME,
    );

    $og_membership = og_group($entity->group_type, $entity->gid, $values);

    if ($entity->entity_type == 'user' && !empty($entity->is_admin)) {
      $cache = &drupal_static(__FUNCTION__, array());
      if (isset($cache["$entity->group_type:$entity->gid"])) {
        $og_roles = $cache["$entity->group_type:$entity->gid"];
      }
      else {
        $og_roles = og_roles($entity->group_type, NULL, $entity->gid);
        $cache["$entity->group_type:$entity->gid"] = $og_roles;
      }
      $rid = array_search(OG_ADMINISTRATOR_ROLE, $og_roles);
      og_role_grant($entity->group_type, $entity->gid, $entity->etid, $rid);
    }

    return array($og_membership->id);
  }

  public function fields() {
    return array(
      'group_type' => 'Group entity type',
      'gid' => 'Group entity id',
      'entity_type' => 'Entity type',
      'etid' => 'Entity ID (e.g. user ID)',
      'state' => 'User\'s group membership can be OG_STATE_ACTIVE or OG_STATE_PENDING. Defaults to OG_STATE_ACTIVE.',
      'created' => 'Create date for this membership. Defaults to REQUEST_TIME',
      'is_admin' => 'Legacy: Is member an administrator in this group. Defaults to No.',
    );
  }
}
