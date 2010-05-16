<?php
// $Id$

/**
 * @file
 * Field module functionality for the Group module.
 */

/*******************************************************************************
 * Hook implementations
 ******************************************************************************/

/**
 * Implement hook_field_info().
 */
function group_field_info() {
  return array(
    'group' => array(
      'label' => t('Group'),
      'description' => t('This field stores groups associated with the content.'),
      'default_widget' => 'group_audience',
      'default_formatter' => 'group_list_default',
      // Entity metadata properties.
      'property_type' => 'group_audience',
      'property_callbacks' => array('group_field_group_property_callback'),
    ),
  );
}

function group_field_group_property_callback(&$info, $entity_type, $field, $instance, $field_type) {
  $name = str_replace('_', '-', $field['field_name']);
  $property = &$info[$entity_type]['bundles'][$instance['bundle']]['properties'][$name];

  // Define a data structure so it's possible to deal with Group
  // audinece properly.
  $property['getter callback'] = 'entity_metadata_field_verbatim_get';
  $property['setter callback'] = 'entity_metadata_field_verbatim_set';
  $property['data info']['properties'] = array(
    'properties' => array(
      'gid' => array(
      'type' => 'integer',
      'label' => t('The group this group content is associated with'),
      ),
      'state' => array(
        'type' => 'text',
        'label' => t('Group content state'),
        'options list' => 'group_group_content_states',
      ),
      'created' => array(
        'type' => 'integer',
        'label' => t('Created timestamp'),
      ),
    ),
  );
}

/**
 * Implement hook_field_schema().
 */
function group_field_schema($field) {
  $columns = array(
    'gid' => array(
      'description' => 'The group unique ID.',
      'type' => 'float',
      'unsigned' => TRUE,
      'not null' => FALSE,
    ),
    'state' => array(
      'description' => 'The state of the group content.',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
      'default' => '',
    ),
    'created' => array(
      'description' => 'The Unix timestamp when the group content was created.',
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ),
  );
  return array(
    'columns' => $columns,
    'indexes' => array(
      'gid' => array('gid'),
    ),
  );
}

/**
 * Implement hook_field_formatter_info().
 */
function group_field_formatter_info() {
  return array(
    'group_list_default' => array(
      'label' => t('Group default list'),
      'field types' => array('group'),
    ),
  );
}

/**
 * Implements hook_field_formatter_view().
 */
function group_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();

  if ($field['field_name'] == 'group_audience' && !empty($items[0])) {
    foreach ($items as $delta => $item) {
      if ($group = group_get_group('group', $item['gid'])) {
        $entity = group_load_entity_from_group($group->gid);
        $label = check_plain(group_entity_get_label($group->entity_type, $group->etid));
        $element[$delta] = array(
          '#type' => 'link',
          '#title' => $label,
          '#href' => "$group->entity_type/$group->etid",
        );
      }
    }
  }

  return $element;
}


/**
 * Implement hook_field_widget_info().
 */
function group_field_widget_info() {
  return array(
    'group_audience' => array(
      'label' => t('Group audience'),
      'settings' => array('opt_group' => 'auto', 'minimum_for_select_list' => 20),
      'field types' => array('group'),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
      ),
    ),
  );
}

/**
 * Implement hook_field_widget_settings_form().
 */
function group_field_widget_settings_form($field, $instance) {
  $widget = $instance['widget'];
  $settings = $widget['settings'];

  $form['opt_group'] = array(
    '#type' => 'radios',
    '#title' => t('Input type'),
    '#description' => t('Select the input type that should be used to get the groups audience. Note that the <em>Never show "other groups"</em> option will show all groups including the ones the user is a not a member of.'),
    '#options' => array(
      'auto' => t('Automatically decide the input according to user permissions (Recommended)'),
      'never' => t('Never show "other groups"'),
      'always' => t('Always show "other groups"'),
    ),
    '#default_value' => !empty($settings['opt_group']) ? $settings['opt_group'] : 'auto',
    '#required' => TRUE,
  );

  $form['minimum_for_select_list'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum for select list'),
    '#description' => t('The minimum number of groups before showing the group as a dropdown list.'),
    '#default_value' => !empty($settings['minimum_for_select_list']) ? $settings['minimum_for_select_list'] : 20,
    '#required' => TRUE,
    '#element_validate' => array('_element_validate_integer_positive'),
  );

  return $form;
}

/**
 * Implement hook_field_widget_form().
 *
 * Unlike options_field_widget_form() our widget's lgroupic is a bit different, as
 * the form element type is a result of the user's access to the groups.
 * For example a privileged user may see all groups as an optgroup select list,
 * where the groups are divided to "My groups" and "Other groups". This means
 * that the $element['#type'] is a result of the options passed to
 * $element['#options'].
 */
function group_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $excludes = array();
  // If it's an existing group, then exclude itself, as in some cases a group
  // can act also as a group content, but we want to prevent associating the
  // group to itself.
  if (!empty($form['#' . $element['#entity_type']])) {
    list($id) = entity_extract_ids($element['#entity_type'], $form['#' . $element['#entity_type']]);
    if (($group = group_get_group($element['#entity_type'], $id))) {
      $excludes[$group->gid] = $group->gid;
    }
  }

  // Determine if a user may see other groups as-well.
  $opt_group = FALSE;

  if ($instance['widget']['settings']['opt_group'] == 'always' || ($instance['widget']['settings']['opt_group'] == 'auto' && user_access('administer group'))) {
    $opt_group = TRUE;
  }

  // Get all the groups a user can see.
  $audience = group_get_audience_options($opt_group);
  foreach (array('content groups', 'other groups') as $key) {
    if (!empty($audience[$key])) {
      $audience[$key] = group_get_groups_label($audience[$key]);
    }
  }

  // The group options presented to the user.
  $options = array();
  if ($opt_group) {
    // Show "My groups" and "Other groups".
    $groups_count = 0;
    if ($my_groups = array_diff_key($audience['content groups'], $excludes)) {
      $options += array(t('My groups') => $my_groups);
      $groups_count = $groups_count + count($my_groups);
    }
    if ($other_groups = array_diff_key($audience['other groups'], $excludes)) {
      $options += array(t('Other groups') => $other_groups);
      $groups_count = $groups_count + count($other_groups);
    }
    $type = 'select';
  }
  else {
    // Show only "My groups".
    $groups_count = count($audience['content groups']);
    $options = array_diff_key($audience['content groups'], $excludes);
    // Show a select list if their are a minimum of groups.
    if ($field['cardinality'] == 1) {
      $type = 'radios';
    }
    else {
      $type = $groups_count >= $instance['widget']['settings']['minimum_for_select_list'] ? 'select' : 'checkboxes';
    }
  }

  if (empty($options)) {
    // There are no group, so don't show any input element.
    $type = 'item';
  }

  if (empty($element['#description'])) {
    $element['#description'] = !empty($groups_count) ? t('Select the groups this content should be associated with.') : t('There are no groups you can select from.');
  }

  $default_values = group_get_context_by_url();

  if (!empty($items)) {
    foreach ($items as $item) {
      $default_values[$item['gid']] = $item['gid'];
    }
  }

  $element['#multiple'] = $multiple = $field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED;;
  // Don't make the field required, if there are no groups.
  $element['#required'] = $element['#required'] && !empty($options);

  // Prepare the type as expected in _options_properties().
  if (in_array($type, array('radios', 'checkboxes'))) {
    $options_type = 'buttons';
  }
  else {
    $options_type = $type;
  }

  $properties = _options_properties($options_type, $element['#multiple'], $element['#required']);

  // If the element isn't required, and there are some options.
  if (!$element['#required'] && $type != 'item') {
    // Use a dummy instance in order to use theme('options_none');
    $dummy_instance['widget']['type'] = 'options_'. $options_type;
    $options = array('_none' => theme('options_none', array('instance' => $dummy_instance))) + $options;
  }

  $element += array(
    // Input should be TRUE only if there are groups that can be selected.
    '#input' => $type != 'item',
    '#type' => $type,
    '#options' => $options,
    '#default_value' => $default_values,
    '#attributes' => array('class' => array('group-audience')),
    '#disabled' => empty($groups_count),
    // Re-use options widget element validation, to correctly transform
    // submitted values from field => delta to delta => field.
    // @see options_field_widget().
    '#value_key' => 'gid',
    '#element_validate' => $type != 'item' ? array('options_field_widget_validate') : array(),
    '#properties' => $properties,
  );

  return $element;
}

/**
 * Implement hook_field_is_empty().
 */
function group_field_is_empty($item, $field) {
  return empty($item['gid']);
}

/**
 * Implement hook_field_insert().
 */
function group_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  group_field_write('insert', $entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implement hook_field_update().
 */
function group_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  group_field_write('update', $entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implement hook_field_attach_insert().
 */
function group_field_attach_insert($entity_type, $entity) {
  group_field_crud_group('insert', $entity_type, $entity);
}

/**
 * Implement hook_field_attach_update().
 */
function group_field_attach_update($entity_type, $entity) {
  group_field_crud_group('update', $entity_type, $entity);
}

/**
 * Implement hook_field_attach_delete().
 */
function group_field_attach_delete($entity_type, $entity) {
  group_field_crud_group('delete', $entity_type, $entity);
}

/*******************************************************************************
 * Helper functions
 ******************************************************************************/
/**
 * Insert or update a field record.
 *
 * @param $op
 *   The operation - "insert" or "update".
 */
function group_field_write($op, $entity_type, $entity, $field, $instance, $langcode, &$items) {
  //FIXME: Only $items['gid'] is populated.
  foreach ($items as &$item) {
    // Set default values.
    $item += array('state' => GROUP_STATE_ACTIVE, 'created' => time());
  }
}

/**
 * Create update or delete a group, based on the field CRUD.
 *
 * @see group_field_attach_insert().
 * @see group_field_attach_update().
 * @see group_field_attach_delete().
 */
function group_field_crud_group($op, $entity_type, $entity) {
  $property = GROUP_GROUP_FIELD;

  $wrapper = &$entity->{$property}[LANGUAGE_NONE];

  // Get the entity ID.
  list($id) = entity_extract_ids($entity_type, $entity);

  $group = group_get_group($entity_type, $id, TRUE, array(GROUP_STATE_ACTIVE, GROUP_STATE_PENDING));
  if ($op == 'delete') {
    if (!empty($group->gid)) {
      // Remove group.
      group_delete(array($group->gid));
    }
  }
  else {
    // Check group is new.
    if (empty($group->gid)) {
      if (!empty($wrapper[0]['value'])) {
        // Save the group to get the group ID.
        group_save($group);

        // Subscribe the entity author, if exists.
        if (!empty($entity->uid) && ($account = user_load($entity->uid))) {
          group_group($group->gid, 'user', $account, GROUP_STATE_ACTIVE);
        }
      }
    }
    else {
      // Existing group.
      $save = FALSE;

      if ($group->state == GROUP_STATE_ACTIVE && empty($wrapper[0]['value'])) {
        $group->state = GROUP_STATE_PENDING;
        $save = TRUE;
      }
      elseif($group->state == GROUP_STATE_PENDING && !empty($wrapper[0]['value'])) {
        $group->state = GROUP_STATE_ACTIVE;
        $save = TRUE;
      }

      if ($save) {
        group_save($group);
      }
    }

    // Determine if field has changed and roles should be overridden, or
    // reverted, by comparing the default access field of the entity being
    // saved, and its original state.
    $property = GROUP_DEFAULT_ACCESS_FIELD;
    if (!empty($entity->{$property})) {
      $original_entity = entity_load($entity_type, array($id));
      $original_entity = reset($original_entity);

      if ($entity->{$property} != $original_entity->{$property}) {
        if ($entity->{$property}[LANGUAGE_NONE][0]['value']) {
          group_user_roles_override($group->gid);
        }
        else {
          // If the field is set to be using default access and there are
          // already overridden roles we delete them.
          group_delete_user_roles_by_group($group->gid);
        }
      }
    }
  }
}