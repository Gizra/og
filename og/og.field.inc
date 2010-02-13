<?php
// $Id$

/**
 * @file
 * Field module functionality for the Organic groups module.
 */

/*******************************************************************************
 * Hook implementations
 ******************************************************************************/

/**
 * Implement hook_field_info().
 */
function og_field_info() {
  return array(
    'og' => array(
      'label' => t('Organic groups'),
      'description' => t('This field stores group nodes.'),
      'default_widget' => 'og_audience',
      'default_formatter' => 'og_list_default',
      // Entity metadata properties.
      'property_type' => 'og_audience',
      'property_callbacks' => array('og_field_og_property_callback'),
    ),
  );
}

function og_field_og_property_callback(&$info, $entity_type, $field, $instance, $field_type) {
  $name = str_replace('_', '-', $field['field_name']);
  $property = &$info[$entity_type]['bundles'][$instance['bundle']]['properties'][$name];

  // Define a data structure so it's possible to deal with organic groups
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
        'options list' => 'og_group_content_states',
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
function og_field_schema($field) {
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
function og_field_formatter_info() {
  return array(
    'og_list_default' => array(
      'label' => t('Organic groups default list'),
      'field types' => array('og'),
    ),
  );
}

/**
 * Implements hook_field_formatter_view().
 */
function og_field_formatter_view($object_type, $object, $field, $instance, $langcode, $items, $display) {
  $element = array();

  if ($field['field_name'] == 'og_audience') {
    foreach ($items as $delta => $item) {
      if ($group = og_get_group('gid', $item['gid'], FALSE)) {
        $label = check_plain(og_entity_get_label($group->obj_type, $group->oid));
        $element[$delta] = array(
          '#type' => 'link',
          '#title' => $label,
          '#href' => "$group->obj_type/$group->oid",
        );
      }
    }
  }

  return $element;
}


/**
 * Implement hook_field_widget_info().
 */
function og_field_widget_info() {
  return array(
    'og_audience' => array(
      'label' => t('Organic groups audience'),
      'settings' => array('opt_group' => 'auto', 'minimum_for_select_list' => 20),
      'field types' => array('og'),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
      ),
    ),
  );
}

/**
 * Implement hook_field_widget_settings_form().
 */
function og_field_widget_settings_form($field, $instance) {
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
 * Unlike options_field_widget_form() our widget's logic is a bit different, as
 * the form element type is a result of the user's access to the groups.
 * For example a privileged user may see all groups as an optgroup select list,
 * where the groups are divided to "My groups" and "Other groups". This means
 * that the $element['#type'] is a result of the options passed to
 * $element['#options'].
 */
function og_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $excludes = array();
  // If it's an existing group, then exclude itself, as in some cases a group
  // can act also as a group content, and we want to prevent associating the group
  // to itself.
  if (!empty($form['#' . $element['#object_type']])) {
    list($id) = entity_extract_ids($element['#object_type'], $form['#' . $element['#object_type']]);
    if (($group = og_get_group($element['#object_type'], $id, FALSE))) {
      $excludes[$group->gid] = $group->gid;
    }
  }

  // Get all the groups a user can see, sanitized with check_plain().
  // @see og_get_all_groups_for_form().
  $content_groups = og_allowed_values($field);

  // Determine if a user may see other groups as-well.
  $opt_group = FALSE;

  if ($instance['widget']['settings']['opt_group'] == 'always' || ($instance['widget']['settings']['opt_group'] == 'auto' && user_access('administer organic groups'))) {
    $opt_group = TRUE;
  }

  // The group options presented to the user.
  $options = array();
  if ($opt_group) {
    // Show "My groups" and "Other groups".
    $groups_count = 0;
    if ($my_groups = array_diff_key($content_groups['content groups'], $excludes)) {
      $options += array(t('My groups') => $my_groups);
      $groups_count = $groups_count + count($my_groups);
    }
    if ($other_groups = array_diff_key($content_groups['other groups'], $excludes)) {
      $options += array(t('Other groups') => $other_groups);
      $groups_count = $groups_count + count($other_groups);
    }
    $type = 'select';
  }
  else {
    // Show only "My groups".
    $groups_count = count($content_groups['content groups']);
    $options = array_diff_key($content_groups['content groups'], $excludes);
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

  if (!empty($element['#description'])) {
    $description = $element['#description'];
  }
  else {
    $description = !empty($groups_count) ? t('Select the groups this content should be associated with.') : t('There are no groups you can select from.');
  }

  $default_values = array();
  // Select groups if they were passed in the URL, in the form of
  // node/add/post?gids_gid[]=1,2,3&gids_node[]=4,5,6
  // Note that gids_ is the prefix followed by the object type (e.g. "node",
  // "user") or "gid" to indicate the passed values are group ID.
  foreach (array_merge(array_keys(entity_get_info()), array('gid')) as $obj_type) {
    $oids =  !empty($_REQUEST['gids_' . $obj_type][0]) ? explode(',', $_REQUEST['gids_' . $obj_type][0]) : array();
    if ($oids) {
      if ($gids = og_get_groups($obj_type, $oids)) {
        $gids = current($gids);
        $default_values = array_merge($default_values, $gids);
      }
    }
  }

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
    '#description' => $description,
    '#options' => $options,
    '#default_value' => $default_values,
    '#attributes' => array('class' => array('og-audience')),
    '#disabled' => empty($groups_count),
    // Re-use options widget element validation, to correctly transform
    // submitted values from field => delta to delta => field.
    // @see options_field_widget().
    '#value_key' => 'gid',
    '#element_validate' => $type != 'item' ? array('options_field_widget_validate') : array(),
    '#properties' => $properties,
  );

  $defaults = array('data' => array(), 'state' => '');
  foreach ($items as $delta => $item) {
    $items[$delta] = array_merge($defaults, $items[$delta]);
  }
  return $element;
}

/**
 * Implement hook_field_is_empty().
 */
function og_field_is_empty($item, $field) {
  return empty($item['gid']);
}

/**
 * Implement hook_field_insert().
 */
function og_field_insert($obj_type, $object, $field, $instance, $langcode, &$items) {
  og_field_write('insert', $obj_type, $object, $field, $instance, $langcode, $items);
}

/**
 * Implement hook_field_update().
 */
function og_field_update($obj_type, $object, $field, $instance, $langcode, &$items) {
  og_field_write('update', $obj_type, $object, $field, $instance, $langcode, $items);
}

/**
 * Implement hook_field_attach_insert().
 */
function og_field_attach_insert($obj_type, $object) {
  og_field_crud_group('insert', $obj_type, $object);
}

/**
 * Implement hook_field_attach_update().
 */
function og_field_attach_update($obj_type, $object) {
  og_field_crud_group('update', $obj_type, $object);
}

/**
 * Implement hook_field_attach_delete().
 */
function og_field_attach_delete($obj_type, $object) {
  og_field_crud_group('delete', $obj_type, $object);
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
function og_field_write($op, $obj_type, $object, $field, $instance, $langcode, &$items) {
  foreach ($items as &$item) {
    $item['state'] = !empty($item['state']) ? $item['state'] : OG_STATE_ACTIVE;
    $item['created'] = !empty($item['created']) ? $item['created'] : time();
  }
}

/**
 * Create update or delete a group, based on the field CRUD.
 *
 * @see og_field_attach_insert().
 * @see og_field_attach_update().
 * @see og_field_attach_delete().
 */
function og_field_crud_group($op, $obj_type, $object) {
  // Get the object ID.
  list($id) = entity_extract_ids($obj_type, $object);

  $group = og_get_group($obj_type, $id, TRUE);

  if ($op == 'delete') {
    if ($group) {
      // Remove group.
      og_delete(array($group->gid));
    }
  }
  else {
    // Check group doesn't already exist. We don't check the $op for insert, as
    // content might have been saved, but not as a group.
    if (!empty($object->og_group[LANGUAGE_NONE][0]['value']) && !empty($group->is_new)) {
      // Save the group.
      og_save($group);

      if (!empty($object->uid)) {
        // Subscribe the object author, if exists.
        $account = user_load($object->uid);
        og_group($group->gid, 'user', $account, OG_STATE_ACTIVE);
      }
    }
    elseif (empty($group->is_new)) {
      $state = '';
      if ($group->state == OG_STATE_ACTIVE && empty($object->og_group[LANGUAGE_NONE][0]['value'])) {
        $state = OG_STATE_PENDING;
      }
      elseif($group->state == OG_STATE_PENDING && !empty($object->og_group[LANGUAGE_NONE][0]['value'])) {
        $state = OG_STATE_ACTIVE;
      }
      if ($state) {
        $group->state = $state;
        // Change the group state.
        og_save($group);
      }
    }
  }
}