<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Group membership'),
  'description' => t('Migrate membership information from audience field to group membership entity.'),
  'migrate callback' => 'og_og_migrate_group_membership',
);

/**
 * Add group membership enteties instead of field data.
 */
function og_og_migrate_group_membership(&$context) {
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['last_vid'] = 0;
    $context['sandbox']['last'] = 0;

    // Calculate max items.
    $query = new EntityFieldQuery();
    $context['sandbox']['max'] = $query
      ->fieldCondition(OG_AUDIENCE_FIELD, 'gid', 1, '>=')
      ->count()
      ->execute();

    if (!$context['sandbox']['max']) {
      // No data to process, so return.
      $context['finished'] = 1;
      return;
    }
  }

  $batch_size = variable_get('og_batch_size', 200);

  $query = new EntityFieldQuery();
  $result = $query
    ->fieldCondition(OG_AUDIENCE_FIELD, 'gid', 1, '>=')
    ->range($context['sandbox']['progress'], $context['sandbox']['progress'] + $batch_size)
    ->execute();

  if (!empty($result)) {
    foreach ($result as $entity_type => $values) {
      foreach ($values as $etid => $value) {
        $entity = entity_load($entity_type, array($etid));
        $entity = reset($entity);
        foreach ($entity->{OG_AUDIENCE_FIELD}[LANGUAGE_NONE] as $value) {
          // Check group membership doesn't exist already.
          if (!og_get_group_membership($value['gid'], $entity_type, $etid)) {
            $group_membership = og_membership_create($value['gid'], $entity_type, $etid, array('state' => $value['state'], 'created' => $value['created']));
            $group_membership->save();
            $params = array(
              '@label' => og_label($value['gid']),
              '@type' => $entity_type,
              '@id' => $etid,
            );
            $context['results'][] = t('Membership of group @label for @type @id', $params);

          }
          $context['sandbox']['progress']++;
        }
      }
    }
  }

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}