<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Group content nodes'),
  'description' => t('Upgrade group content'),
  'access callback' => 'og_migrate_7000_access',
  'dependencies' => array('og_7000_group'),
);


/**
 * OG Migrate callback; Upgrade "group content" nodes.
 */
function og_7000_group_content(&$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last'] = 0;

    // Calculate max items.
    $query = db_select('node', 'n');
    $query->innerJoin('d6_og_ancestry', 'oga', 'n.nid = oga.nid');
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

  // Operate on every revision of every node, in batches.
  $batch_size = variable_get('og_batch_size', 200);

  $query = db_select('node', 'n');
  $query->innerJoin('d6_og_ancestry', 'oga', 'n.nid = oga.nid');
  $result = $query
    ->fields('n', array('nid', 'type', 'created'))
    ->fields('oga', array('upgrade_id', 'group_nid'))
    ->condition('oga.upgrade_id', $context['sandbox']['last'], '>')
    ->orderBy('oga.upgrade_id', 'ASC')
    ->range(0, $batch_size)
    ->execute();

  foreach ($result as $row) {
    $node = node_load($row->nid);

    // Make sure entity isn't already associated with group.
    if (!og_is_member('node', $row->group_nid, 'node', $node)) {
      $values = array('entity_type' => 'node', 'entity' => $row->nid);
      og_group('node', $row->group_nid, $values);
    }

    $context['sandbox']['last'] = $row->upgrade_id;
    $context['sandbox']['progress']++;
  }
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}
