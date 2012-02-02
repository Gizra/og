<?php


/**
 * OG selection handler.
 */
class OgSelectionHandler extends EntityReference_SelectionHandler_Generic {

  /**
   * Implements EntityReferenceHandler::getInstance().
   */
  public static function getInstance($field, $instance) {
    return new OgSelectionHandler($field, $instance);
  }

 /**
   * Override settings form().
   */
  public static function settingsForm($field, $instance) {
    $form = parent::settingsForm($field, $instance);
    $entity_type = $field['settings']['target_type'];
    $entity_info = entity_get_info($entity_type);

    $bundles = array();
    foreach ($entity_info['bundles'] as $bundle_name => $bundle_info) {
      if (og_is_group_type($entity_type, $bundle_name)) {
        $bundles[$bundle_name] = $bundle_info['label'];
      }
    }

    if (!$bundles) {
      $form['target_bundles'] = array(
        '#type' => 'item',
        '#title' => t('Target bundles'),
        '#markup' => t('Error: The selected "Target type" %entity does not have bundles that are a group type', array('%entity' => $entity_info['label'])),
      );
    }
    else {

      $settings = $field['settings']['handler_settings'];
      $settings += array(
        'target_bundles' => array(),
        'membership_type' => OG_MEMBERSHIP_TYPE_DEFAULT,
        'reference_type' => 'my_groups',
        'primary_field' => FALSE,
        'hide_secondary_field' => TRUE,
      );

      $form['target_bundles'] = array(
        '#type' => 'select',
        '#title' => t('Target bundles'),
        '#options' => $bundles,
        '#default_value' => $settings['target_bundles'],
        '#size' => 6,
        '#multiple' => TRUE,
        '#description' => t('The bundles of the entity type acting as group, that can be referenced. Optional, leave empty for all bundles.')
      );

      $options = array();
      foreach (og_membership_type_load() as $og_membership) {
        $options[$og_membership->name] = $og_membership->description;
      }
      $form['membership_type'] = array(
        '#type' => 'select',
        '#title' => t('OG membership type'),
        '#description' => t('Select the membership type that will be used for a subscribing user.'),
        '#options' => $options,
        '#default_value' => $settings['membership_type'],
        '#required' => TRUE,
      );

      $form['reference_type'] = array(
        '#type' => 'select',
        '#title' => t('Reference'),
        '#options' => array(
          'my_groups' => t('My groups'),
          'other_groups' => t('Other groups'),
          'all_groups' => t('All groups'),
        ),
        '#description' => t('What groups should be referenced.'),
        '#default_value' => $settings['reference_type'],
      );

      $options = array('0' => t('None'));

      // Get all the other group audience fields in this bundle.
      $entity_type = $instance['entity_type'];
      $bundle = $instance['bundle'];
      $fields_info = field_info_fields();
      foreach (field_info_instances($entity_type, $bundle) as $field_name => $field_instance) {
        if ($field_name == $field['field_name']) {
          // This is the current field.
          continue;
        }
        if ($fields_info[$field_name]['type'] != 'entityreference' || $fields_info[$field_name]['settings']['handler'] != 'og') {
          // This is not an Entity reference field.
          continue;
        }

        if (!empty($fields_info[$field_name]['settings']['handler_settings']['primary_field'])) {
          // Field is already a secondary field.
          continue;
        }
        $options[$field_name] = $field_instance['label'] . ' (' . $field_name . ')';
      }

      $form['primary_field'] = array(
        '#type' => 'select',
        '#title' => t('Primary field'),
        '#description' => t('Select a field that will be populated with the values of this field.'),
        '#options' => $options,
        '#default_value' => $settings['primary_field'],
        '#required' => TRUE,
        '#element_validate' => array('og_handler_primary_field_validate'),
      );

      $form['hide_secondary_field'] = array(
        '#type' => 'checkbox',
        '#title' => t('Hide secondary field'),
        '#description' => t('Show the secondary field only to users with "administer group" permission.'),
        '#default_value' => $settings['hide_secondary_field'],
        '#states' => array(
          'invisible' => array(
            ':input[name="field[settings][handler_settings][primary_field]"]' => array('value' => 0),
          ),
        ),
      );
      form_load_include($form_state, 'php', 'og', '/plugins/selection/og.class');
    }

    return $form;
  }

  /**
   * Build an EntityFieldQuery to get referencable entities.
   */
  public function buildEntityFieldQuery($match = NULL, $match_operator = 'CONTAINS') {
    $handler = EntityReference_SelectionHandler_Generic::getInstance($this->field, $this->instance);
    $query = $handler->buildEntityFieldQuery($match, $match_operator);

    // The "node_access" tag causes errors, so we replace it with
    // "entity_field_access" tag instead.
    // @see _node_query_node_access_alter().
    unset($query->tags['node_access']);
    $query->addTag('entity_field_access');
    $query->addTag('og');

    $group_type = $this->field['settings']['target_type'];
    $entity_info = entity_get_info($group_type);

    if (!field_info_field(OG_GROUP_FIELD)) {
      // There are no groups, so falsify query.
      $query->propertyCondition($entity_info['entity keys']['id'], -1, '=');
      return $query;
    }

    // Show only the entities that are active groups.
    $query->fieldCondition(OG_GROUP_FIELD, 'value', 1, '=');


    $user_groups = og_get_groups_by_user(NULL, $group_type);
    $reference_type = $this->field['settings']['handler_settings']['reference_type'];
    // Show the user only the groups they belong to.
    if ($reference_type == 'my_groups') {
      if ($user_groups && !empty($this->instance) && $this->instance['entity_type'] == 'node') {
        // Check if user has "create" permissions on those groups.
        $node_type = $this->instance['bundle'];
        $ids = array();
        foreach ($user_groups as $gid) {
          if (og_user_access($group_type, $gid, "create $node_type content")) {
            $ids[] = $gid;
          }
        }
      }
      else {
        $ids = $user_groups;
      }

      if ($ids) {
        $query->propertyCondition($entity_info['entity keys']['id'], $ids, 'IN');
      }
      else {
        // User doesn't have permission to select any group so falsify this
        // query.
        $query->propertyCondition($entity_info['entity keys']['id'], -1, '=');
      }
    }
    elseif ($reference_type == 'other_groups' && $user_groups) {
      // Show only group the user doesn't belong to.
      $query->propertyCondition($entity_info['entity keys']['id'], $user_groups, 'NOT IN');
    }

    return $query;
  }

  public function entityFieldQueryAlter(SelectQueryInterface $query) {
    $handler = EntityReference_SelectionHandler_Generic::getInstance($this->field, $this->instance);
    // FIXME: Allow altering, after fixing http://drupal.org/node/1413108
    // $handler->entityFieldQueryAlter($query);
  }
}

/**
 * Validate handler; Check primary field.
 */
function og_handler_primary_field_validate($element, $form_state) {
  if (empty($form_state['values']['instance'])) {
    // Field doesn't exist yet.
    return;
  }

  $field_name = $form_state['values']['field']['settings']['handler_settings']['primary_field'];
  if (!$field_name) {
    return;
  }

  // Check the primary field has the same target type, bundle and membership
  // type as the secondary one.
  $primary_field = field_info_field($field_name);
  $secondary_field = $form_state['values']['field'];
  if ($primary_field['settings']['target_type'] != $secondary_field['settings']['target_type']) {
    form_error($element, t('Primary field target type does not match the secondary field.'));
  }
  elseif (!empty($primary_field['settings']['handler_settings']['target_bundles']) && $primary_field['settings']['handler_settings']['target_bundles'] != $secondary_field['settings']['handler_settings']['target_bundles']) {
    // Primary field defines bundles, but they are not the same as the
    // secondary.
    form_error($element, t('Primary field target bundles do not match the secondary field.'));
  }

  if ($primary_field['settings']['handler_settings']['membership_type'] != $secondary_field['settings']['handler_settings']['membership_type']) {
    form_error($element, t('Primary field membership type does not match the secondary field.'));
  }

  $entity_type = $form_state['values']['instance']['entity_type'];
  $bundle = $form_state['values']['instance']['bundle'];
}
