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
    'og_group_subscribe' => array(
      'label' => t('Organic groups subscription'),
      'field types' => array('list_boolean'),
    ),
  );
}



/**
 * Implement hook_field_schema().
 */
function og_field_schema($field) {
  $columns = array(
    'value' => array(
      'type' => 'float',
      'unsigned' => TRUE,
      'not null' => FALSE,
    ),
    'data' => array(
      'description' => 'Serialized additional data about the group post.',
      'type' => 'text',
      'not null' => FALSE,
      'serialize' => TRUE,
    ),
    'state'     => array(
      'description'     => 'The state of the group post.',
      'type'            => 'varchar',
      'length'          => 255,
      'not null'        => FALSE,
      'default' => '',
    ),
  );
  return array(
    'columns' => $columns,
    'indexes' => array(
      'value' => array('value'),
    ),
  );
}

/**
 * Implement hook_field_widget_info().
 */
function og_field_widget_info() {
  return array(
    'og_audience' => array(
      'label' => t('Organic groups audience'),
      'field types' => array('og'),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
      ),
    ),
  );
}

/**
 * Implement hook_field_widget().
 */
function og_field_widget(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  dpm($element);
  // If it's an existing group, then exclude itself, as in some cases a group
  // can act also as a group post, and we want to prevent associating the group
  // to itself.
  if (!empty($form['#' . $element['#object_type']])) {
    list($id) = entity_extract_ids($element['#object_type'], $form['#' . $element['#object_type']]);
    if ($group = og_get_group($element['#object_type'], $id)) {
      $element['#excludes'][] = $group->gid;
    }
  }



  // Allow showing the form element for another user.
  if (!empty($element['uid'])) {
    $account = user_load($element['uid']);
  }
  else {
    global $user;
    $account = $user;
  }

  $content_groups = og_allowed_values($field, $account);
  $field_key  = 'value';

  // Determine if a user may see other groups as-well.
  $opt_group = FALSE;

  if (user_access('administer organic groups')) {
    $opt_group = TRUE;
  }
  elseif ($element['#opt_group'] && $element['#opt_group_access_check']) {
    $opt_group = TRUE;
  }
  // The group options presented to the user.
  $options = array();
  $element['#excludes'] = drupal_map_assoc($element['#excludes']);
  if ($opt_group) {
    // Show "My groups" and "Other groups".
    $groups_count = 0;
    if ($my_groups = array_diff_key($content_groups['content groups'], $element['#excludes'])) {
      $options += array(t('My groups') => $my_groups);
      $groups_count = $groups_count + count($my_groups);
    }
    if ($other_groups = array_diff_key($content_groups['other groups'], $element['#excludes'])) {
      $options += array(t('Other groups') => $other_groups);
      $groups_count = $groups_count + count($other_groups);
    }
    $type = 'select';
  }
  else {
    // Show only "My groups".
    $groups_count = count($content_groups['content groups']);
    $options = array_diff_key($content_groups['content groups'], $element['#excludes']);
    // Show a select list if their are a minimum of groups.
    if ($field['cardinality'] == 1) {
      $type = 'radios';
    }
    else {
      $type = $groups_count >= $element['#minimum_for_select_list'] ? 'select' : 'checkboxes';
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

  // Select groups if they were passed in the URL, in the form of
  // node/add/post?gids[]=1,2,3
  $default_values = !empty($_REQUEST['gids'][0]) ? explode(',', $_REQUEST['gids'][0]) : array();

  if (!empty($items)) {
    foreach ($items as $item) {
      $default_values[$item['value']] = $item['value'];
    }
  }

  $element += array(
    '#type' => $type,
    '#title' => !empty($element['#title']) ? $element['#title'] : t('Group audience'),
    '#description' => $description,
    '#options' => $options,
    '#default_value' => $default_values,
    '#required' => isset($element['#required']) ? $element['#required'] : $instance['required'],
    '#multiple' => isset($element['#multiple']) ? $element['#multiple'] : $field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED,
    '#attributes' => !empty($element['#attributes']) ? $element['#attributes'] : array('class' => array('og-audience')),
    '#disabled' => empty($groups_count),
    // Re-use options widget element validation, to correctly transform
    // submitted values from field => delta to delta => field.
    // @see options_field_widget().
    '#value_key' => 'value',
    '#element_validate' => array('og_field_widget_validate'),
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
  return empty($item['value']);
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
function og_field_attach_insert($obj_type, $object, $skip_fields = array()) {
  og_field_crud_group($obj_type, $object, $skip_fields);
}

/**
 * Implement hook_field_attach_presave().
 */
function og_field_attach_update($obj_type, $object, $skip_fields = array()) {
  og_field_crud_group($obj_type, $object, $skip_fields);
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
    $item['data'] = !empty($item['data']) ? $item['data'] : array();
    $alter = array('state' => $item['state'], 'data' => $item['data']);

    // Allow other modules to alter the data.
    drupal_alter('og_field_' . $op, $alter, $obj_type, $object, $field, $instance, $langcode, $items);
    $item['state'] = $alter['state'];
    $item['data'] = serialize($alter['data']);
  }
}

/**
 * Add or remove a group, based on the field CRUD.
 *
 * @see og_field_attach_pre_insert().
 * @see og_field_attach_pre_update().
 */
function og_field_crud_group($obj_type, $object, $skip_fields = array()) {
  if(!empty($object->og_group)) {
    // Get the object ID.
    list($id) = entity_extract_ids($obj_type, $object);

    // Check group doesn't already exist.
    if (!($group = og_get_group($obj_type, $id, array(OG_STATE_ACTIVE, OG_STATE_PENDING)))) {
      // Add the group.
      $group = og_set_group($obj_type, $id);

      if (!empty($object->uid)) {
        // Subscribe the object author, if exists.
        $account = user_load($object->uid);
        og_subscribe_user(array(array('value' => $group->gid, 'state' => OG_STATE_ACTIVE)), $account);
      }

      og_set_new_group_roles($group->gid);
    }
    else {
      $state = '';
      if ($group->state == OG_STATE_ACTIVE && empty($object->og_group[FIELD_LANGUAGE_NONE][0]['value'])) {
        $state = OG_STATE_PENDING;
      }
      elseif($group->state == OG_STATE_PENDING && !empty($object->og_group[FIELD_LANGUAGE_NONE][0]['value'])) {
        $state = OG_STATE_ACTIVE;
      }
      if ($state) {
        // Change the group state.
        og_set_group($group->obj_type, $group->oid, $state, $group->data);
      }
    }
  }
}

/*******************************************************************************
 * Theme functions
 ******************************************************************************/

/**
 * Organic group audience list field formatter.
 *
 * @return
 *   A link to the group node.
 */
function theme_field_formatter_og_list_default($variables) {
  $element = $variables['element'];
  $return = '';
  if ($group = og_get_group('gid', $element['#item']['value'])) {
    $label = check_plain(og_entity_get_label($group->obj_type, $group->oid));
    // Return the group's link.
    $return = l($label, "$group->obj_type/$group->oid");
  }

  return $return;
}

/**
 * Organic group audience list field formatter.
 *
 * @return
 *   A link to the group node.
 */
function theme_field_formatter_og_group_subscribe($variables) {
  $return = '';

  $element = $variables['element'];
  if ($element['#item']['value']) {
    global $user;
    $obj_type = $element['#object_type'];
    list($id) = entity_extract_ids($obj_type, $element['#object']);

    $object= current(entity_load($obj_type, array($id)));

    if (($group = og_get_group($obj_type, $id)) && (empty($object->uid) || $object->uid != $user->uid)) {
      // Check user association with the group.
      $links = array();

      if ($user_group = og_get_group_from_object($group->gid, 'user', $user)) {
        // check the user state.
        if ($user_group['state'] == OG_STATE_PENDING && og_user_access($group->gid, 'subscribe')) {
          $links['title'] = t('Re-request group membership');
          $links['href'] = "og/$obj_type/$id/subscribe";
        }
        elseif ($user_group['state'] == OG_STATE_ACTIVE && og_user_access($group->gid, 'unsubscribe')) {
          $links['title'] = t('Unsubscribe from group');
          $links['href'] = "og/$obj_type/$id/unsubscribe";
        }
      }
      else {
        if (og_user_access($group->gid, 'subscribe without approval')) {
          $links['title'] = t('Subscribe to group');
        }
        elseif (og_user_access($group->gid, 'subscribe')) {
          $links['title'] = t('Request group membership');
        }
        $links['href'] = "og/$obj_type/$id/subscribe";
      }

      if (!empty($links['title'])) {
        $return = l($links['title'], $links['href']);
      }
    }
  }

  return $return;
}

