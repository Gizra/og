<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('User relation to group'),
  'access callback' => 'og_migrate_7000_access',
  'dependencies' => array('og_7000_group'),
);

/**
 * OG Migrate callback; Upgrade user subscription to groups.
 */
function og_7000_user(&$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last'] = 0;

    // Calculate max items.
    $query = db_select('d6_og_uid', 'ogu');
    $context['sandbox']['max'] = $query
      ->countQuery()
      ->execute()
      ->fetchField();

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

  $query = db_select('d6_og_uid', 'ogu');
  $query
    ->fields('ogu')
    ->condition('ogu.upgrade_id', $context['sandbox']['last'], '>')
    ->orderBy('ogu.upgrade_id', 'ASC')
    ->range(0, $batch_size);
  $records = $query->execute();

  foreach ($records as $record) {
    $state = $record->is_active ? OG_STATE_ACTIVE : OG_STATE_PENDING;

    $account = user_load($record->uid);
    $node = node_load($record->nid);
    // Make sure entity isn't already associated with group.
    if (!og_is_member('node', $record->nid, 'user', $account)) {
      $values = array('entity' => $account, 'state' => $state);
      og_group('node', $record->nid, $values);
    }

    if ($record->is_admin) {
      $og_roles = og_roles('node', $node->nid, $node->type);
      $rid = array_search(OG_ADMINISTRATOR_ROLE, $og_roles);
      og_role_grant('node', $record->nid, $record->uid, $rid);
    }
    $context['sandbox']['last'] = $record->upgrade_id;
    $context['sandbox']['progress']++;
    $context['message'] = t('Processing database rows @start - @end', array('@start' => $record->upgrade_id, '@end' => $record->upgrade_id + $batch_size));
  }

  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}