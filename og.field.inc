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
 * Implements hook_field_widget_properties_alter().
 *
 * Depends on http://drupal.org/node/806830
 */
function og_field_widget_properties_alter(&$widget, $context) {
  $field = $context['field'];
  if ($field['field_name'] == OG_AUDIENCE_FIELD) {
    $instance = $context['instance'];
    // Determine if a user may see other groups as-well.
    $opt_group = FALSE;

    if ($instance['widget']['settings']['opt_group'] == 'always' || ($instance['widget']['settings']['opt_group'] == 'auto' && user_access('administer group'))) {
      $opt_group = TRUE;
    }

    $widget['type'] = $instance['widget']['settings']['minimum_for_autocomplete'] <= og_field_audience_count($opt_group) ? OG_AUDIENCE_AUTOCOMPLETE_WIDGET : OG_AUDIENCE_WIDGET;
  }
}

/**
 * Implement hook_field_info().
 */
function og_field_info() {
  return array(
    'group' => array(
      'label' => t('Group'),
      'description' => t('This field stores groups associated with the content.'),
      'default_widget' => OG_AUDIENCE_WIDGET,
      'default_formatter' => 'og_list_default',
      // Entity metadata properties.
      'property_type' => OG_AUDIENCE_FIELD,
      'property_callbacks' => array('og_field_group_property_callback'),
    ),
  );
}

function og_field_group_property_callback(&$info, $entity_type, $field, $instance, $field_type) {
  $name = str_replace('_', '-', $field['field_name']);
  $property = &$info[$entity_type]['bundles'][$instance['bundle']]['properties'][$name];

  // Define a data structure so it's possible to deal with Group
  // audience field.
  $property['getter callback'] = 'entity_metadata_field_verbatim_get';
  $property['setter callback'] = 'entity_metadata_field_verbatim_set';
  $property['property info'] = array(
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
      'label' => t('Group default list'),
      'field types' => array('group'),
    ),
  );
}

/**
 * Implements hook_field_formatter_view().
 */
function og_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  $element = array();

  if ($field['field_name'] == OG_AUDIENCE_FIELD && !empty($items[0])) {
    foreach ($items as $delta => $item) {
      if ($group = og_get_group('group', $item['gid'])) {
        $label = og_label($group->gid);
        $entity = entity_load($group->entity_type, array($group->etid));
        $entity = current($entity);
        // Get the entity type of the group entity.
        $uri = entity_uri($group->entity_type, $entity);
        $element[$delta] = array(
          '#type' => 'link',
          '#title' => $label,
          '#href' => $uri['path'],
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
  $autocomplete_settings = array(
    'autocomplete_match' => 'contains',
    'size' => 60,
    'autocomplete_path' => 'group/autocomplete',
  );

  return array(
    OG_AUDIENCE_WIDGET => array(
      'label' => t('Group audience'),
      'settings' => array(
        'opt_group' => 'auto',
        'minimum_for_select_list' => 20,
        'minimum_for_autocomplete' => 50,
      ),
      'field types' => array('group'),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
      ),
      'settings' => array(
        'opt_group' => 'auto',
        'minimum_for_select_list' => 20,
        'minimum_for_autocomplete' => 50,
      ) + $autocomplete_settings,
    ),
    OG_AUDIENCE_AUTOCOMPLETE_WIDGET => array(
      'label'       => t('Autocomplete text field'),
      'description' => t('Display the list of referenceable groups as a textfield with autocomplete behaviour.'),
      'field types' => array('group'),
      'settings'    => $autocomplete_settings,
    ),
  );
}

/**
 * Implement hook_field_widget_settings_form().
 */
function og_field_widget_settings_form($field, $instance) {
  $widget   = $instance['widget'];
  $defaults = field_info_widget_settings($widget['type']);
  $settings = array_merge($defaults, $widget['settings']);

  if ($widget['type'] == OG_AUDIENCE_WIDGET) {

    $form['opt_group'] = array(
      '#type' => 'radios',
      '#title' => t('Input type'),
      '#description' => t('Select the input type that should be used to get the groups audience. Note that the <em>Never show "other groups"</em> option will show all groups including the ones the user is a not a member of.'),
      '#options' => array(
        'auto' => t('Automatically decide the input according to user permissions (Recommended)'),
        'never' => t('Never show "other groups"'),
        'always' => t('Always show "other groups"'),
      ),
      '#default_value' => $settings['opt_group'],
      '#required' => TRUE,
    );

    $form['minimum_for_select_list'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum for select list'),
      '#description' => t('The minimum number of groups before showing the group as a dropdown list.'),
      '#default_value' => $settings['minimum_for_select_list'],
      '#required' => TRUE,
      '#element_validate' => array('_element_validate_integer_positive'),
    );

    $form['minimum_for_autocomplete'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum for autocomplete'),
      '#description' => t('The minimum number of groups before showing the group as autocomplete.'),
      '#default_value' => $settings['minimum_for_autocomplete'],
      '#required' => TRUE,
      '#element_validate' => array('_element_validate_integer_positive'),
    );
  }

  $form['autocomplete_match'] = array(
    '#type'             => 'select',
    '#title'            => t('Autocomplete matching'),
    '#default_value'    => $settings['autocomplete_match'],
    '#options'          => array(
      'starts_with'     => t('Starts with'),
      'contains'        => t('Contains'),
    ),
    '#description'      => t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of nodes.'),
  );
  $form['size'] = array(
    '#type'             => 'textfield',
    '#title'            => t('Size of textfield'),
    '#default_value'    => $settings['size'],
    '#element_validate' => array('_element_validate_integer_positive'),
    '#required'         => TRUE,
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
function og_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $base) {
  $element = $base;
  $widget = $instance['widget'];

  switch ($widget['type']) {
    case OG_AUDIENCE_AUTOCOMPLETE_WIDGET:
      $element += array(
        '#type' => 'textfield',
        '#default_value' => isset($items[$delta]['gid']) ? $items[$delta]['gid'] : NULL,
        '#autocomplete_path' => $instance['widget']['settings']['autocomplete_path'] . '/' . $field['field_name'],
        '#size' => $instance['widget']['settings']['size'],
        '#element_validate' => array('og_field_audience_autocomplete_validate'),
        '#value_callback' => 'og_field_audience_autocomplete_value',
      );

      $return = array('gid' => $element);

      break;

    case OG_AUDIENCE_WIDGET:
      $excludes = array();
      // If it's an existing group, then exclude itself, as in some cases a group
      // can act also as a group content, but we want to prevent associating the
      // group to itself.
      if (!empty($form['#' . $element['#entity_type']])) {
        list($id) = entity_extract_ids($element['#entity_type'], $form['#' . $element['#entity_type']]);
        if (($group = og_get_group($element['#entity_type'], $id))) {
          $excludes[$group->gid] = $group->gid;
        }
      }

      // Determine if a user may see other groups as-well.
      $opt_group = FALSE;

      if ($instance['widget']['settings']['opt_group'] == 'always' || ($instance['widget']['settings']['opt_group'] == 'auto' && user_access('administer group'))) {
        $opt_group = TRUE;
      }

      // Get all the groups a user can see.
      $audience = og_field_audience_options($opt_group);
      foreach (array('content groups', 'other groups') as $key) {
        if (!empty($audience[$key])) {
          $audience[$key] = og_label_multiple($audience[$key]);
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

      $default_values = og_get_context_by_url();

      if (!empty($items)) {
        foreach ($items as $item) {
          $default_values[$item['gid']] = $item['gid'];
        }
      }

      $element['#multiple'] = $multiple = $field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED;;
      // Don't make the field required, if there are no groups.
      $element['#required'] = $element['#required'] && !empty($options);

      // Prepare the type as expected in _options_properties().
      $options_type = in_array($type, array('radios', 'checkboxes')) ? 'buttons' : $type;

      $properties = _options_properties($options_type, $element['#multiple'], $element['#required'], $options);

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

      $return = $element;
      break;
  }

  return $return;
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
function og_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  og_field_write('insert', $entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implement hook_field_update().
 */
function og_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  og_field_write('update', $entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implement hook_field_attach_insert().
 */
function og_field_attach_insert($entity_type, $entity) {
  og_field_crud_group('insert', $entity_type, $entity);
}

/**
 * Implement hook_field_attach_update().
 */
function og_field_attach_update($entity_type, $entity) {
  og_field_crud_group('update', $entity_type, $entity);
}

/**
 * Implement hook_field_attach_delete().
 */
function og_field_attach_delete($entity_type, $entity) {
  og_field_crud_group('delete', $entity_type, $entity);
}

/**
 * Implements hook_field_attach_form().
 *
 * Handle translated nodes that act as groups.
 */
function og_field_attach_form($entity_type, $entity, &$form, $form_state, $langcode) {
  $item = menu_get_item();
  if (!empty($form[OG_GROUP_FIELD]) && (($entity_type == 'node' && !empty($entity->tnid) && $entity->tnid != $entity->nid)) || $item['page_callback'] == 'node_add' && !empty($_GET['translation']) && !empty($_GET['target'])) {

    // Prevent changing the group state on nodes that are translated.
    // Load the original node.
    $description = t('You can not change the group state from a translated content.');

    $tnid = !empty($entity->tnid) ? $entity->tnid : $_GET['translation'];
    if (($node = node_load($tnid)) && node_access('update', $node)) {
      $description .= ' ' . t('Changing the group state can only be done via <a href="@node">@title</a>.', array('@node' => url('node/' . $node->nid . '/edit'), '@title' => $node->title));
    }

    $form[OG_GROUP_FIELD][LANGUAGE_NONE]['#options'] = array();
    $form[OG_GROUP_FIELD][LANGUAGE_NONE]['#description'] = $description;
    $form[OG_GROUP_FIELD][LANGUAGE_NONE]['#disabled'] = TRUE;
    $form[OG_GROUP_FIELD][LANGUAGE_NONE]['#required'] = FALSE;
  }
}

/**
 * Insert or update a field record.
 *
 * @param $op
 *   The operation - "insert" or "update".
 */
function og_field_write($op, $entity_type, $entity, $field, $instance, $langcode, &$items) {
  //FIXME: Only $items['gid'] is populated.
  foreach ($items as &$item) {
    // Set default values.
    $item += array('state' => OG_STATE_ACTIVE, 'created' => time());
  }
}

/**
 * Create update or delete a group, based on the field CRUD.
 *
 * @see og_field_attach_insert().
 * @see og_field_attach_update().
 * @see og_field_attach_delete().
 */
function og_field_crud_group($op, $entity_type, $entity) {
  $property = OG_GROUP_FIELD;

  if (!empty($entity->{$property}) && empty($entity->og_skip_group_create)) {
    $wrapper = &$entity->{$property}[LANGUAGE_NONE];

    // Get the entity ID.
    list($id) = entity_extract_ids($entity_type, $entity);

    $group = og_get_group($entity_type, $id, TRUE, array(OG_STATE_ACTIVE, OG_STATE_PENDING));
    if ($op == 'delete') {
      if (!empty($group->gid)) {
        // Remove group.
        $group->delete();
      }
    }
    else {
      // Check group is new.
      if (empty($group->gid)) {
        if (!empty($wrapper[0]['value'])) {
          // Save the group to get the group ID.
          $group->save();

          // Subscribe the entity author, if exists.
          if (!empty($entity->uid) && ($account = user_load($entity->uid))) {
            og_group($group->gid, 'user', $account, OG_STATE_ACTIVE);
          }
        }
      }
      else {
        // Existing group.
        $save = FALSE;

        if ($group->state == OG_STATE_ACTIVE && empty($wrapper[0]['value'])) {
          $group->state = OG_STATE_PENDING;
          $save = TRUE;
        }
        elseif($group->state == OG_STATE_PENDING && !empty($wrapper[0]['value'])) {
          $group->state = OG_STATE_ACTIVE;
          $save = TRUE;
        }

        // Check if the entity label has changed.
        $label = og_entity_label($entity_type, $entity);

        if ($group->label != $label) {
          $group->label = $label;
          $save = TRUE;
        }

        if ($save) {
          $group->save();
        }
      }

      // Determine if field has changed and roles should be overridden, or
      // reverted, by comparing the default access field of the entity being
      // saved, and its original state.
      $property = OG_DEFAULT_ACCESS_FIELD;
      // The field exists.
      if (isset($entity->{$property})) {
        if (!empty($entity->{$property}[LANGUAGE_NONE][0]['value'])) {
          og_user_roles_override($group->gid);
        }
        elseif (empty($group->is_new)) {
          // If the field is set to be using default access and there are
          // already overridden roles we delete them.
          og_delete_user_roles_by_group($group->gid);
        }
      }
    }
  }
}


/**
 * Get an array of allowed values for OG audience field.
 *
 * @return
 *   Array keyed by "content groups" and "other groups".
 */
function og_field_audience_options($opt_group = FALSE, $account = NULL) {
  $return = &drupal_static(__FUNCTION__, array());

  if (!empty($return)) {
    return $return;
  }

  if (empty($account)) {
    global $user;

    $account = clone($user);
  }

  //Initialize values
  $return = array('content groups' => array(), 'other groups' => array());

  $content_groups = og_get_entity_groups('user', $account);

  $return['content groups'] = $content_groups;

  if ($opt_group) {
    // Get all existing group.
    $all_groups = og_get_all_group();
    $return['other groups'] = array_diff($all_groups, $content_groups);
  }

  // Let other modules change the list.
  // TODO: Is it possible to do it in a more general way?
  drupal_alter('og_audience_options', $return, $opt_group, $account);

  return $return;
}

/**
 * Return the number of groups a user may see in the group audience field.
 */
function og_field_audience_count($opt_group = FALSE, $account = NULL) {
  $count = &drupal_static(__FUNCTION__, FALSE);

  if ($count === FALSE) {
    if (empty($account)) {
      global $user;

      $account = clone($user);
    }
    // If opt_group is TRUE then get the count of all the groups that are
    // active, otherwise only the ones the user belongs to.
    $count = $opt_group ? og_get_all_group(array(OG_STATE_ACTIVE), array('count' => TRUE)) : count(og_get_entity_groups('user', $account));
  }

  return $count;
}



/**
 * Implements hook_field_validate().
 *
 * FIXME: Adapt this function to Group.
 *
 * Possible error codes:
 * - 'invalid_nid': nid is not valid for the field (not a valid node id, or the node is not referenceable).
 */
function __group_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
  // Extract nids to check.
  $ids = array();

  // First check non-numeric "nid's to avoid losing time with them.
  foreach ($items as $delta => $item) {
    if (is_array($item) && !empty($item['nid'])) {
      if (is_numeric($item['nid'])) {
        $ids[] = $item['nid'];
      }
      else {
        $errors[$field['field_name']][$langcode][$delta][] = array(
          'error' => 'invalid_nid',
          'message' => t("%name: invalid input.",
             array('%name' => $instance['label'])),
        );
      }
    }
  }
  // Prevent performance hog if there are no ids to check.
  if ($ids) {
    $refs = og_field_audience_potential_groups('', NULL, $ids);
    foreach ($items as $delta => $item) {
      if (is_array($item)) {
        if (!empty($item['nid']) && !isset($refs[$item['nid']])) {
          $errors[$field['field_name']][$langcode][$delta][] = array(
            'error' => 'invalid_nid',
            'message' => t("%name: this post can't be referenced.",
              array('%name' => $instance['label'])),
          );
        }
      }
    }
  }
}


/**
 * Value callback for a node_reference autocomplete element.
 *
 * Replace the node nid with a node title.
 */
function og_field_audience_autocomplete_value($element, $input = FALSE, $form_state) {
  if ($input === FALSE) {
    // We're building the displayed 'default value': expand the raw nid into
    // "Group title [gid:n]".
    if (!empty($element['#default_value']) && $group = og_load($element['#default_value'])) {
      $value = og_label($group->gid);
      $value .= ' [gid:' . $group->gid . ']';
      return $value;
    }
  }
}

/**
 * Validation callback for a group audience autocomplete element.
 */
function og_field_audience_autocomplete_validate($element, &$form_state, $form) {
  $field = $form_state['field'][$element['#field_name']][$element['#language']]['field'];
  $instance = $form_state['field'][$element['#field_name']][$element['#language']]['instance'];

  $value = $element['#value'];
  $gid = NULL;

  if (!empty($value)) {
    // Check whether we have an explicit "[gid:n]" input.
    preg_match('/^(?:\s*|(.*) )?\[\s*gid\s*:\s*(\d+)\s*\]$/', $value, $matches);
    if (!empty($matches)) {
      // Explicit gid. Check that the 'title' part matches the actual title for
      // the nid.
      list(, $label, $gid) = $matches;
      if (!empty($label)) {
        if ($label != og_label($gid)) {
          form_error($element, t('%name: label mismatch. Please check your selection.', array('%name' => $instance['label'])));
        }
      }
    }
    else {
      // No explicit gid (the submitted value was not populated by autocomplete
      // selection). Get the gid of a referencable node from the entered title.
      if ($reference = og_field_audience_potential_groups($value, 'equals', NULL, 1)) {
        $gid = key($reference);
      }
      else {
        form_error($element, t('%name: found no valid group with that label.', array('%name' => $instance['label'])));
      }
    }
  }

  // Set the element's value as the node id that was extracted from the entered
  // input.
  form_set_value($element, $gid, $form_state);
}

/**
 * Implements hook_field_widget_error().
 */
function og_field_widget_error($element, $error, $form, &$form_state) {
  form_error($element, $error['message']);
}

/**
 * Fetch an array of all candidate groups.
 *
 * This info is used in various places (allowed values, autocomplete
 * results, input validation...). Some of them only need the nids,
 * others nid + titles, others yet nid + titles + rendered row (for
 * display in widgets).
 *
 * The array we return contains all the potentially needed information,
 * and lets consumers use the parts they actually need.
 *
 * @param $field
 *   The field description.
 * @param $string
 *   Optional string to filter titles on (used by autocomplete).
 * @param $match
 *   Operator to match filtered name against, can be any of:
 *   'contains', 'equals', 'starts_with'
 * @param $ids
 *   Optional node ids to lookup (the $string and $match arguments will be
 *   ignored).
 * @param $limit
 *   If non-zero, limit the size of the result set.
 *
 * @return
 *   An array of valid nodes in the form:
 *   array(
 *     gid => 'rendered' -- The text to display in widgets (can be HTML)
 *   );
 *  @todo Check whether we still need the 'rendered' value (hook_options_list()
 *  does not need it anymore). Should probably be clearer after the 'Views'
 *  mode is ported.
 */
function og_field_audience_potential_groups($string = '', $match = 'contains', $ids = array(), $limit = NULL) {
  $results = &drupal_static(__FUNCTION__, array());

  // Create unique id for static cache.
  $cid = $match . ':'
    . ($string !== '' ? $string : implode('-', $ids))
    . ':' . $limit;
  if (!isset($results[$cid])) {
    $groups = og_field_audience_potential_groups_standard($string, $match, $ids, $limit);

    // Store the results.
    $results[$cid] = !empty($groups) ? $groups : array();
  }

  return $results[$cid];
}

/**
 * Helper function for og_field_audience_potential_groups().
 */
function og_field_audience_potential_groups_standard($string = '', $match = 'contains', $ids = array(), $limit = NULL) {
  $query = og_get_all_group(array(OG_STATE_ACTIVE), array('return query' => TRUE));

  $query->addField('og', 'label');
  if ($string !== '') {
    $args = array();
    switch ($match) {
      case 'contains':
        $title_clause = 'label LIKE :match';
        $args['match'] = '%' . $string . '%';
        break;

      case 'starts_with':
        $title_clause = 'label LIKE :match';
        $args['match'] = $string . '%';
        break;

      case 'equals':
      default: // no match type or incorrect match type: use "="
        $title_clause = 'label = :match';
        $args['match'] = $string;
        break;
    }
    $query->where($title_clause, $args);
  }
  elseif ($ids) {
    $query->condition('gid', $ids, 'IN');
  }

  $query->orderBy('label');

  if ($limit) {
    $query->range(0, $limit);
  }

  $gids = $query->execute()->fetchAllKeyed();
  $groups = og_load_multiple(array_keys($gids));
  foreach ($groups as $group) {
    $label = og_label($group->gid);
    $return[$group->gid] = $label;
  }
  return $return;
}

/**
 * Menu callback for the autocomplete results.
 */
function og_field_audience_autocomplete($field_name, $string = '') {
  $field = field_info_field($field_name);

  $match = isset($field['widget']['autocomplete_match']) ? $field['widget']['autocomplete_match'] : 'contains';
  $matches = array();

  $groups = og_field_audience_potential_groups($string, $match, array(), 10);
  foreach ($groups as $gid => $label) {
    // Add a class wrapper for a few required CSS overrides.
    $matches[$label . " [gid:$gid]"] = '<div class="group-autocomplete">' . $label . '</div>';
  }
  drupal_json_output($matches);
}