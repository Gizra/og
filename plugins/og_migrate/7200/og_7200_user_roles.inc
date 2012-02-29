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
    $context['sandbox']['last'] = 0;

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

  $result = db_select('og_users_roles', 'ogur')
    ->fields('ogur', array('uid', 'rid', 'gid'))
    ->condition('group_type', '', '=')
    ->condition('uid', $context['sandbox']['last'], '>=')
    ->range(0, $batch_size)
    ->orderBy('uid')
    ->execute()
    ->fetchAll();

  if (empty($result)) {
    // No data to process, so return.
    $context['finished'] = 1;
    return;
  }

  $global_roles = og_get_global_roles();

  foreach ($result as $row) {
    list($group_type, $gid) = og_migrate_get_group($row->gid);

    // Get the bundle from the entity.
    $group = entity_load_single($group_type, $gid);
    list(,,$bundle) = entity_extract_ids($group_type, $group);

    // Get the per-bundle roles, and replace the role ID, with the
    // per-bundle role ID.
    $og_roles = og_roles($group_type, $gid, $bundle);
    $rid = array_search($global_roles[$row->rid], $og_roles);

    db_update('og_users_roles')
      ->fields(array('rid' => $rid, 'group_type' => $group_type, 'gid' => $gid))
      ->condition('uid', $row->uid, '=')
      ->condition('rid', $row->rid, '=')
      ->condition('gid', $row->gid, '=')
      ->execute();

    $context['sandbox']['progress']++;
    $context['sandbox']['last'] = $row->uid;
  }

  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}