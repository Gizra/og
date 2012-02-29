<?php

/**
 * Plugin definition.
 */
$plugin = array(
  'name' => t('Upgrade OG membership - 7.2'),
  'description' => t('Re-populate group ID and group entity, deprecating the OG group entity.'),
  'access callback' => 'og_migrate_7200_access',
);

/**
 * OG Migrate callback; Upgrade OG membership.
 */
function og_7200_og_membership(&$context) {
  if (empty($context['sandbox'])) {
    // Calculate max items.
    $query = new EntityFieldQuery();
    $context['sandbox']['max'] = $query
      ->entityCondition('entity_type', 'og_membership')
      ->propertyCondition('group_type', '', '=')
      ->count()
      ->execute();

    if (!$context['sandbox']['max']) {
      // No data to process, so return.
      $context['finished'] = 1;
      return;
    }

    $context['audience_fields'] = array();
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last'] = 0;

    // Indicate if there is a group-audience field referencing the entity
    // type.
    foreach (field_info_fields() as $field_name => $field) {
      if (og_is_group_audience_field($field_name)) {
        $group_type = $field['settings']['target_type'];
        $context['audience_fields'][$group_type][$field_name] = $field['bundles'];
      }
    }
  }

  // Operate on entity in batches.
  $batch_size = variable_get('og_batch_size', 200);

  $query = new EntityFieldQuery();
  $result = $query
    ->entityCondition('entity_type', 'og_membership')
    ->propertyCondition('group_type', '', '=')
    ->propertyCondition('id', $context['sandbox']['last'], '>')
    ->propertyOrderBy('id')
    ->range(0, $batch_size)
    ->execute();

  if (empty($result['og_membership'])) {
    // No data to process, so return.
    $context['finished'] = 1;
    return;
  }

  $og_memberships = og_membership_load_multiple(array_keys($result['og_membership']));
  foreach ($og_memberships as $og_membership) {
    list($group_type, $gid) = og_migrate_get_group($og_membership->gid);
    if (empty($gid)) {
      // The OG membership was somehow not deleted when the group was deleted,
      // so do it now.
      $og_membership->delete();
      continue;
    }

    // Re-populate the group ID and add the group type.
    $og_membership->group_type = $group_type;
    $og_membership->gid = $gid;

    $entity_type = $og_membership->entity_type;

    $entity = entity_load_single($entity_type, $og_membership->etid);
    if (!$entity) {
      // The OG membership was somehow not deleted when the entity was deleted,
      // so do it now.
      $og_membership->delete();
      continue;
    }
    list(,,$bundle) = entity_extract_ids($og_membership->entity_type, $entity);

    $field_name = $group_type == 'node' ? OG_AUDIENCE_FIELD : OG_AUDIENCE_FIELD . '_' . $group_type;

    if (empty($context['audience_fields'][$group_type][$field_name][$entity_type]) || !array_search($bundle, $context['audience_fields'][$group_type][$field_name][$entity_type])) {
      // Add a group-audience field to the bundle.
      $og_field = og_fields_info(OG_AUDIENCE_FIELD);
      $og_field['field']['settings']['target_type'] = $group_type;

      $entity_info = entity_get_info($group_type);
      $og_field['instance']['label'] = t('Groups audience - @label', array('@label' => $entity_info['label']));
      $context['audience_fields'][$group_type][$field_name][$entity_type][] = $bundle;

      og_create_field($field_name, $entity_type, $bundle, $og_field);
    }

    $og_membership->field_name = $field_name;
    $og_membership->save();

    $context['sandbox']['last'] = $og_membership->id;
    $context['sandbox']['progress']++;
  }

  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}