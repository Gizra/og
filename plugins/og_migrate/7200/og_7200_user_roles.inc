<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Upgrade user roles - 7.2'),
  'description' => t('Update the group ID in user roles.'),
  'access callback' => 'og_migrate_7200_access',
  'dependencies' => array('og_7200_roles'),
);

/**
 * OG Migrate callback; Create per bundle OG roles.
 */
function og_7200_user_roles(&$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last_uid'] = 0;
    $context['sandbox']['last_rid'] = 0;
    $context['sandbox']['last_gid'] = 0;

    // Calculate max items.
    $context['sandbox']['max'] = db_select('og_users_roles', 'ogur')
      ->condition('group_type', '', '=')
      ->countQuery()
      ->execute()
      ->fetchField();

    if (!$context['sandbox']['max']) {
      // No data to process, so return.
      $context['finished'] = 1;
      return;
    }
  }

  // Operate on entity in batches.
  $batch_size = variable_get('og_batch_size', 200);

  $query = db_select('og_users_roles', 'ogur')
    ->fields('ogur', array('uid', 'rid', 'gid'))
    ->condition('group_type', '', '=')
    ->range(0, $batch_size)
    ->orderBy('uid')
    ->orderBy('rid')
    ->orderBy('gid');

  // Create a complex query condition to ensure that this loop picks up exactly where last one left off.
  // Any simpler condition can potentially lead to infinite loops and/or loops that finish too early.
  // The assembled condition is:
  // (uid>last_uid) || (uid==last_uid && rid>last_rid) || (uid==last_uid && rid==last_rid && gid>last_gid)
  $db_or = db_or();
  $db_or->condition('uid', $context['sandbox']['last_uid'], '>');

  $db_and = db_and();
  $db_and->condition('uid', $context['sandbox']['last_uid'], '=');
  $db_and->condition('rid', $context['sandbox']['last_rid'], '>');
  $db_or->condition($db_and);

  $db_and = db_and();
  $db_and->condition('uid', $context['sandbox']['last_uid'], '=');
  $db_and->condition('rid', $context['sandbox']['last_rid'], '=');
  $db_and->condition('gid', $context['sandbox']['last_gid'], '>');
  $db_or->condition($db_and);

  $query->condition($db_or);

  $result = $query
    ->execute()
    ->fetchAll();

  if (empty($result)) {
    // No data to process, so return.
    $context['finished'] = 1;
    return;
  }

  // Fetch the global roles.
  $default_roles = og_roles('', '');

  foreach ($result as $row) {
    list($group_type, $gid) = og_migrate_get_group($row->gid);

    // Update context early to ensure that it gets done for each row, regardless of subsequent processing
    $context['sandbox']['progress']++;
    $context['sandbox']['last_uid'] = $row->uid;
    $context['sandbox']['last_rid'] = $row->rid;
    $context['sandbox']['last_gid'] = $row->gid;

    // Some installations might have missing entities, so we don't assume
    // they exist.
    if ($gid && $group = entity_load_single($group_type, $gid)) {
      // Get the bundle from the entity.
      list(,,$bundle) = entity_extract_ids($group_type, $group);

      // Get the per-bundle roles, and replace the role ID, with the
      // per-bundle role ID.
      $og_roles = og_roles($group_type, $bundle, $gid);
      if (!$rid = array_search($default_roles[$row->rid], $og_roles)) {
        continue;
      }

      db_update('og_users_roles')
        ->fields(array('rid' => $rid, 'group_type' => $group_type, 'gid' => $gid))
        ->condition('uid', $row->uid, '=')
        ->condition('rid', $row->rid, '=')
        ->condition('gid', $row->gid, '=')
        ->execute();
    }
  }

  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

