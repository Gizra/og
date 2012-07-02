<?php

/**
 * @file
 * Drush commands for Organic groups migrate.
 */

/**
 * Implements hook_drush_help().
 */
function og_migrate_drush_help($section) {
  switch ($section) {
    case 'og migrate:og-migrate':
      return dt("Migrate all or some available og plugins");
  }
}

/**
 * Implements hook_drush_command().
 */
function og_migrate_drush_command() {
  $items['og-migrate'] = array(
    'description' => 'Migrate some or all plugins.',
    'arguments' => array(
      'name' => 'An optional list of OG migrate plugins. If not specified, all available plugins will be executed.',
    ),
    'drupal dependencies' => array('og_migrate'),
  );

  return $items;
}

/**
 * Command callback.
 */
function drush_og_migrate() {
  $plugins_names = drush_og_migrate_get_plugins(_convert_csv_to_array(func_get_args()));
  if (empty($plugins_names)) {
    return drush_set_error('OG_MIGRATE_NOTHING_TO_DO', dt('No plugins to migrate.'));
  }
  drush_log(dt('The following plugins will be executed: !plugins', array('!plugins' => implode(', ', $plugins_names))), 'status');
  if (!drush_confirm(dt('Do you really want to continue?'))) {
    return drush_user_abort();
  }
  foreach ($plugins_names as $name) {
    $plugins[$name] = $name;
  }
  drush_og_migrate_plugins($plugins);

  drush_log(dt('Migration complete'), 'status');
}

/**
 * Execute migration plugins in a batch.
 */
function drush_og_migrate_plugins($plugins_names) {
  $plugins_names = og_migrate_get_plugins_dependencies($plugins_names);
  if (!$plugins_names) {
    return;
  }
  // Add the operations.
  $plugins = og_migrate_get_plugins();
  foreach ($plugins_names as $plugins_name) {
    $operations[] = array($plugins[$plugins_name]['migrate callback'], array());
  }
  $batch = array(
    'title' => t('Migrating Organic groups data'),
    'operations' => $operations,
    'finished' => 'og_migrate_finished',
    'init_message' => t("Organic groups migration is starting."),
    'progress_message' => t('Processed @current out of @total.'),
    'error_message' => t('Organic groups migration has encountered an error.'),
    // This is our own key, used to retrieve the list of plugins that were
    // called.
    'og_migrate_plugins' => $plugins_names,
  );

  batch_set($batch);
  $batch =& batch_get();
  $batch['progressive'] = FALSE;
  drush_backend_batch_process();
}


/**
 * Get accessible plugins.
 */
function drush_og_migrate_get_plugins($requests = array()) {
  $plugins = og_migrate_get_accessible_plugins();

  $plugins_names = array_keys($plugins);
  if (!empty($requests) && !empty($plugins_names)) {
    $not_found = array_diff($requests, $plugins_names);
    $plugins_names = array_intersect($plugins_names, $requests);
    if (!empty($not_found)) {
      return drush_set_error('OG_MIGRATE_PLUGIN_NOT_FOUND', dt('The following plugins were not found: !plugins', array('!plugins' => implode(', ', $not_found))));
    }
  }
  return $plugins_names;
}
