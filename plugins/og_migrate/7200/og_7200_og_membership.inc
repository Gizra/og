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

    // Add a group-audience field to the bundles.
    foreach (variable_get('og_migrate_7200_field_instances', array()) as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        $og_field = og_fields_info(OG_AUDIENCE_FIELD);
        $og_field['field']['settings']['target_type'] = $group_type;

        $entity_info = entity_get_info($entity_type);
        $og_field['instance']['label'] = t('Groups audience - @label', array('@label' => $entity_info['label']));

        $field_name = OG_AUDIENCE_FIELD . '_' . $entity_type . '_' . $bundle;
        og_create_field($field_name, $entity_type, $bundle, $og_field);
      }
    }

    variable_del('og_migrate_7200_field_instances');

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

    $context['sandbox']['progress'] = 0;
    $context['sandbox']['last'] = 0;
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

    $field_name = OG_AUDIENCE_FIELD . '_' . $group_type . '_' . $bundle;

    $og_membership->field_name = $field_name;
    $og_membership->save();

    $context['sandbox']['last'] = $og_membership->id;
    $context['sandbox']['progress']++;
  }

  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}