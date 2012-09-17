<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Migrate OGUR module data - 7.2'),
  'description' => t('Migrate data from Drupal 6.x og_user_roles (OGUR) module.'),
  'access callback' => 'og_migrate_ogur_access',
  'dependencies' => array('og__user_roles'),
);

/**
 * Access callback for OGUR related migration items.
 */
// function og_migrate_ogur_access() {
  // return db_table_exists('d6_og_users_roles');
// }

/**
 * OG Migrate callback; Upgrade user roles in groups.
 */
function og__ogur(&$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last'] = 0;

    // Calculate max items.
    $query = db_select('d6_og_users_roles', 'ogur');
    $context['sandbox']['max'] = $query->countQuery()->execute()->fetchField();

    // Create OG roles and map to drupal roles.
    $context['sandbox']['roles'] = array();
    $drupal_roles = db_select('d6_og_users_roles', 'ogur')
      ->fields('ogur', array('rid'))
      ->groupBy('rid')
      ->orderBy('rid', 'ASC')
      ->execute()->fetchCol();
    // For each role, enumerate over all possible types and their respective
    // bundles.
    foreach ($drupal_roles as $drupal_role) {
      foreach(og_get_all_group_bundle() as $type => $bundles) {
        foreach($bundles as $bundle => $name) {
          $role = user_role_load($drupal_role);
          $og_role = og_role_create($role->name, $type, 0, $bundle);
          og_role_save($og_role);
          $context['sandbox']['roles'][$role->rid] = $og_role->rid;
        }
      }
    }

    if (!$context['sandbox']['max']) {
      // No data to process, so return.
      $context['finished'] = 1;
      return;
    }
  }

  if (!$context['sandbox']['max']) {
    // No data to process, so return.
    $context['finished'] = 1;
    return;
  }

  // Operate on users in batches.
  $batch_size = variable_get('og_batch_size', 200);

  $query = db_select('d6_og_users_roles', 'ogur');
  $records = $query->fields('ogur')
    ->condition('ogur.upgrade_id', $context['sandbox']['last'], '>')
    ->orderBy('ogur.upgrade_id', 'ASC')
    ->range(0, $batch_size)
    ->execute();

  foreach ($records as $group) {
    og_role_grant('node', $group->gid, $group->uid, $context['sandbox']['roles'][$group->rid]);
    $context['sandbox']['last'] = $group->upgrade_id;
    $context['sandbox']['progress']++;
    $context['message'] = t('Processing database rows @start - @end', array('@start' => $group->upgrade_id, '@end' => $group->upgrade_id + $batch_size));
  }

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}
