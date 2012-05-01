<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Remove global roles - 7.2'),
  'description' => t('Remove the global roles.'),
  'access callback' => 'og_migrate_7200_access',
  'dependencies' => array('og_7200_user_roles'),
);

/**
 * OG Migrate callback; Remove global roles.
 */
function og_7200_remove_global_roles(&$context) {
  db_delete('og_role')
    ->condition('gid', 0)
    ->condition('group_type', '')
    ->condition('group_bundle', '')
    ->execute();

  $context['finished'] = 1;
}
