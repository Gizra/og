<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Upgrade OG roles - 7.2'),
  'description' => t('Add per-bundle OG roles, based on the global roles.'),
  'access callback' => 'og_migrate_7200_access',
);

/**
 * OG Migrate callback; Create per bundle OG roles.
 */
function og_7200_roles(&$context) {
  foreach (og_get_all_group_bundle() as $group_type => $bundles) {
    foreach ($bundles as $bundle => $label) {
      og_roles_override($group_type, 0, $bundle);
    }
  }

  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last'] = 0;

    // Calculate max items.
    $context['sandbox']['max'] = db_select('og_role', 'ogr')
      ->condition('gid', 0, '>')
      ->condition('group_type', '', '=')
      ->condition('group_bundle', '', '=')
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

  $result = db_select('og_role', 'ogr')
    ->fields('ogr', array('rid', 'gid'))
    ->condition('gid', 0, '>')
    ->condition('group_type', '', '=')
    ->condition('group_bundle', '', '=')
    ->condition('rid', $context['sandbox']['last'], '>')
    ->range(0, $batch_size)
    ->orderBy('rid')
    ->execute()
    ->fetchAll();

  if (empty($result)) {
    // No data to process, so return.
    $context['finished'] = 1;
    return;
  }

  foreach ($result as $row) {
    list($group_type, $gid) = og_migrate_get_group($row->gid);
    $group = entity_load_single($group_type, $gid);
    list(,,$bundle) = entity_extract_ids($group_type, $group);

    db_update('og_role')
      ->fields(array('group_type' => $group_type, 'gid' => $gid, 'group_bundle' => $bundle))
      ->condition('rid', $row->rid, '=')
      ->execute();

    $context['sandbox']['progress']++;
    $context['sandbox']['last'] = $row->rid;
  }

  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}