<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Upgrade OG roles - 7.2'),
  'description' => t('Add per-bundle OG roles.'),
  'access callback' => 'og_migrate_7200_access',
);

/**
 * OG Migrate callback; Create per bundle OG roles.
 */
function og_7200_roles(&$context) {
  // On first call to batch, copy all existing global roles to
  // bundle-specific versions. Although similar processing is available
  // through the og_roles_override() function, special handling is
  // necessary to ensure that custom global roles are copied as well as
  // default global roles.
  if (empty($context['sandbox'])) {
    // Get list of global roles and their permissions.
    // This call to og_roles searches the database for all roles where
    // bundle and type are blank and gid = 0. Such entries should only
    // exist when a pre-2.0 version of og has not been fully migrated.
    $og_roles = og_roles('', '', 0);
    if (!empty($og_roles)) {
      $perms = og_role_permissions($og_roles);
    }
    else {
      // Just to be safe, revert to standard list of global default roles
      // if no matches were found.
      $og_roles = og_get_default_roles();
      $perms = og_get_default_permissions();
    }

    foreach (og_get_all_group_bundle() as $group_type => $bundles) {
      foreach ($bundles as $bundle => $label) {
        foreach ($og_roles as $rid => $name) {
          // Copy each role and its permissions to each bundle.
          // Although og_roles_override() does a db query at this point to
          // remap og_user_roles, not necessary in this case
          // (handled by og_7200_user_roles).
          $role = og_role_create($name, $group_type, 0, $bundle);
          og_role_save($role);
          og_role_change_permissions($role->rid, $perms[$rid]);
        }
      }
    }

    // Set up batch for subsequent loops.
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

  // Update non-global roles in batches.
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
    // Some installations might have missing entities, so we don't assume
    // they exist.
    if ($gid && $group = entity_load_single($group_type, $gid)) {
      list(,,$bundle) = entity_extract_ids($group_type, $group);

      db_update('og_role')
        ->fields(array('group_type' => $group_type, 'gid' => $gid, 'group_bundle' => $bundle))
        ->condition('rid', $row->rid, '=')
        ->execute();
    }

    $context['sandbox']['progress']++;
    $context['sandbox']['last'] = $row->rid;
  }

  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}
