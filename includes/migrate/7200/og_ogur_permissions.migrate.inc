<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Update OGUR module permissions - 7.2'),
  'description' => t('Grant permissions based on migrated data from Drupal 6.x og_user_roles (OGUR) module.'),
  'access callback' => 'og_migrate_ogur_access',
  'dependencies' => array('og__ogur'),
);

/**
 * Access callback for OGUR related migration items.
 */
// function og_migrate_ogur_access() {
  // return db_table_exists('d6_og_users_roles');
// }

/**
 * OG Migrate callback; Upgrade OGUR user roles.
 */
function og__ogur_permissions(&$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last'] = 0;

    // Find the role ids for the legacy og_user_roles.
    $context['sandbox']['legacy_roles'] = array();
    $variables = db_select('variable', 'var')
      ->fields('var')
      ->condition('name', 'og_user_roles_%', 'LIKE')
      ->execute();
    foreach ($variables as $var) {
      $name = $var->name;
      $value = variable_get($name);
      if ($name == 'og_user_roles_default_admin_role') {
        $context['sandbox']['legacy_roles']['default_admin'] = $value;
      }
      elseif ($name == 'og_user_roles_default_role') {
        $context['sandbox']['legacy_roles']['default'] = $value;
      }
      // Get a list of all roles managed by the different OG content types.
      // In D6, the permissions were site-wide, so we don't need to
      // remember which group "managed" which role, we just need to include
      // all the managed roles on the list.
      elseif (strpos($name, 'og_user_roles_roles_') === 0) {
        $key = substr($name, 20);
        $count = 0;
        foreach ($value as $rid => $enabled) {
          if ($enabled) {
            $context['sandbox']['legacy_roles'][$key . $count++] = $rid;
          }
        }
      }
    }

    // With our legacy roles calculate max items.
    $context['sandbox']['max'] = db_select('role_permission', 'rp')
      ->condition('rid', $context['sandbox']['legacy_roles'], 'IN')
      ->countQuery()
      ->execute()
      ->fetchField();

    // Map legacy roles to their new equivelant roles.
    $context['sandbox']['role_map'] = array();
    $query = db_select('og_role', 'og');
    $query->join('role', 'legacy', 'og.name = legacy.name');
    $roles = $query->fields('og', array('rid', 'name'))
      ->fields('legacy', array('rid', 'name'))
      ->execute();
    foreach ($roles as $role) {
      $context['sandbox']['role_map'][$role->legacy_rid] = $role->rid;
    }
  }

  if (!$context['sandbox']['max']) {
    // No data to process, so return.
    $context['finished'] = 1;
    return;
  }

  // Get new default role ids for duplicating permissions to legacy "default" roles.
  $og_roles = db_select('og_role', 'og_role')
    ->fields('og_role', array('rid', 'name'))
    ->execute();
  foreach ($og_roles as $og_role) {
    if ($og_role->name == OG_ADMINISTRATOR_ROLE) {
      $default_admin_rid = $og_role->rid;
    }
    elseif ($og_role->name == OG_AUTHENTICATED_ROLE) {
      $default_rid = $og_role->rid;
    }
  }

  // Operate on permissions in batches.
  $batch_size = variable_get('og_batch_size', 200);

  // Grant permissions from legacy roles to current roles.
  $permissions = db_select('role_permission', 'perm')
    ->fields('perm')
    ->condition('rid', $context['sandbox']['legacy_roles'], 'IN')
    ->range($context['sandbox']['progress'], $batch_size)
    ->execute();
  foreach ($permissions as $perm) {
    $permission = $perm->permission;
    if (strpos($permission, 'edit') !== FALSE) {
      // The permissions name is "update" and not "edit".
      $permission = 'update' . substr($permission, 4);
    }

    og_role_grant_permissions($context['sandbox']['role_map'][$perm->rid], array($permission));

    // Replicate permissions to new "default" roles.
    if ($perm->rid == $context['sandbox']['legacy_roles']['default']) {
      og_role_grant_permissions($default_rid, array($permission));
    }
    elseif ($perm->rid == $context['sandbox']['legacy_roles']['default_admin']) {
      og_role_grant_permissions($default_admin_rid, array($permission));
    }
    $context['sandbox']['progress']++;
    $context['message'] = t('Processing database rows @start - @end', array('@start' => $context['sandbox']['progress'], '@end' => $context['sandbox']['progress'] + $batch_size));
  }

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}
