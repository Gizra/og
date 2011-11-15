<?php


/**
 * @file
 * Field module functionality for the Organic groups module.
 */

/**
 * Implements hook_field_info().
 */
function og_field_info() {
  return array(
    OG_AUDIENCE_FIELD_TYPE => array(
      'label' => t('OG group audience'),
      'description' => t('This field stores groups associated with the content.'),
      'default_widget' => OG_AUDIENCE_WIDGET,
      'default_formatter' => 'og_list_default',
      // We do not expose the field as entity property, but we provide a
      // separate property making use of the membership entities instead.
    ),
  );
}

/**
 * Implements hook_field_formatter_info().
 */
function og_field_formatter_info() {
  return array(
    'og_list_default' => array(
      'label' => t('OG audience list'),
      'field types' => array(OG_AUDIENCE_FIELD_TYPE),
    ),
  );
}

/**
 * Implements hook_field_formatter_view().
 */
function og_field_formatter_view($entity_type, $entity, $field, $instance, $langcode, $items, $display) {
  if (!$items) {
    return;
  }

  $element = array();
  foreach ($items as $delta => $item) {
    if ($item['gid']) {
      if (empty($item['label'])) {
        // We are in node preview mode, for simplicity we return without showing
        // a real preview.
        return;
      }

      $element[$delta] = array(
        '#type' => 'link',
        '#title' => $item['label'],
        '#href' => $item['uri'],
      );
    }
    else {
      $element[$delta] = array(
        '#markup' => '- ' . t('Private group') . ' -',
      );
    }
  }

  return $element;
}

/**
 * Implements hook_field_formatter_prepare_view().
 */
function og_field_formatter_prepare_view($entity_type, $entities, $field, $instances, $langcode, &$items, $displays) {
  if (!class_exists('OgMetadataController')) {
    // We might be in update.php and the metadata class isn't loaded yet.
    return;
  }
  $private = FALSE;
  foreach ($entities as $entity) {
    $wrapper = entity_metadata_wrapper($entity_type, $entity);
    if (!$wrapper->og_membership->value()) {
      return;
    }
    $id = $wrapper->getIdentifier();
    $membership_type = $instances[$id]['widget']['settings']['membership_type'];

    foreach ($wrapper->og_membership as $delta => $og_membership_wrapper) {
      // Show the membership only if it's according to the field settings.
      if ($membership_type != $og_membership_wrapper->type->value(array('identifier' => TRUE))) {
        continue;
      }
      $group_wrapper = $og_membership_wrapper->group;
      $group = $group_wrapper->value();
      if ($group->access()) {
        $items[$id][] = array(
          'gid' => $group->gid,
          'label' => og_label($group->gid),
          'uri' => $group_wrapper->url->value(),
        );
      }
      else {
        // Private group.
        $private[$id] = $id;
      }
    }
  }
  if (!$private) {
    return;
  }
  foreach ($private as $id) {
    // Add the private label only once, in the end of the list.
    $items[$id][] = array(
      'gid' => 0,
      'label' =>  '- ' . t('Private group') . ' -',
    );
  }
}


/**
 * Implements hook_field_widget_info().
 */
function og_field_widget_info() {
  return array(
    OG_AUDIENCE_WIDGET => array(
      'label' => t('OG group audience'),
      'field types' => array(OG_AUDIENCE_FIELD_TYPE),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_CUSTOM,
      ),
      'settings' => array(
        'opt_group' => 'auto',
        'membership_type' => OG_MEMBERSHIP_TYPE_DEFAULT,
      ),
    ),
    OG_AUDIENCE_AUTOCOMPLETE_WIDGET => array(
      'label'       => t('Autocomplete text field'),
      'description' => t('Display the list of referenceable groups as a textfield with autocomplete behaviour.'),
      'field types' => array(OG_AUDIENCE_FIELD_TYPE),
      'settings'    => array(
        'autocomplete_match' => 'contains',
        'size' => 60,
        'autocomplete_path' => 'group/autocomplete',
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_settings_form().
 */
function og_field_widget_settings_form($field, $instance) {
  $widget   = $instance['widget'];
  $defaults = field_info_widget_settings($widget['type']);
  $settings = array_merge($defaults, $widget['settings']);
  $form = array();

  if ($widget['type'] == OG_AUDIENCE_WIDGET) {

    $form['opt_group'] = array(
      '#type' => 'radios',
      '#title' => t('Input type'),
      '#description' => t('Select the input type that should be used to get the groups audience. Note that the <em>Always show "other groups"</em> option will show all groups including the ones the user is a not a member of.'),
      '#options' => array(
        'auto' => t('Automatically decide the input according to user permissions (Recommended)'),
        'never' => t('Never show "other groups"'),
        'always' => t('Always show "other groups"'),
      ),
      '#default_value' => $settings['opt_group'],
      '#required' => TRUE,
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
  }
  elseif ($widget['type'] == OG_AUDIENCE_AUTOCOMPLETE_WIDGET) {
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
      '#element_validate' => array('element_validate_integer_positive'),
      '#required'         => TRUE,
    );
  }

  return $form;
}

/**
 * Implements hook_field_widget_form().
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

  $form_state['entity_type'] = $instance['entity_type'];
  $entity_type = $form_state['entity_type'];
  $form_state['bundle'] = $instance['bundle'];

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
      // Determine if a user may see other groups as-well.
      $opt_group = FALSE;

      if ($instance['widget']['settings']['opt_group'] == 'always' || ($instance['widget']['settings']['opt_group'] == 'auto' && user_access('administer group'))) {
        $opt_group = TRUE;
      }

      // Get all the groups divided to "content groups" and "other groups".
      $audience = og_field_audience_options($opt_group);

      // Get all groups that should be excluded.
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

      // Get default values from URL, or from the edited entity.
      $default_values = og_get_context_by_url();
      // Keep the group ID of the selected items, as they might be needed again,
      // and we don't want to iterate over the items again.
      $items_gid = array();
      $membership_type = $instance['widget']['settings']['membership_type'];
      if (!empty($form_state[$entity_type])) {
        $wrapper = entity_metadata_wrapper($entity_type, $form_state[$entity_type]);
        foreach ($wrapper->og_membership->value() as $og_membership) {
          if ($membership_type != $og_membership->type) {
            continue;
          }
          $gid = $og_membership->gid;
          $default_values[$gid] = $gid;
        }
      }

      foreach (array('content groups', 'other groups') as $key) {
        if (!empty($audience[$key])) {
          // Get the label un-sanitized, as they will be later sanitized
          // according to the form type.
          $audience[$key] = og_label_multiple($audience[$key], FALSE);
        }
      }

      // The group options presented to the user.
      $options = array();
      $hidden_selected_gids = array();
      $type = 'select';
      if ($opt_group) {
        // Show "My groups" and "Other groups".
        if ($my_groups = array_diff_key($audience['content groups'], $excludes)) {
          $options += array(t('My groups') => $my_groups);
        }
        if ($other_groups = array_diff_key($audience['other groups'], $excludes)) {
          $options += array(t('Other groups') => $other_groups);
        }
      }
      else {
        // Show only "My groups".
        $options = array_diff_key($audience['content groups'], $excludes);
        // If there are items that are already selected but the user doesn't
        // have access to them, we need to keep track of them.
        $hidden_selected_gids = array_diff($default_values, array_keys($options));
      }

      if (empty($options)) {
        // There are no group, so don't show any input element.
        $type = 'item';
        $element['#description'] = t('There are no groups you can select from.');
        $properties = array();
      }
      else {
        if (empty($element['#description'])) {
          $element['#description'] = t('Select the groups this content should be associated with.');
        }

        $element['#multiple'] = $field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED;;
        // Don't make the field required, if there are no groups.
        $element['#required'] = $element['#required'] && !empty($options);

        $properties = _options_properties($type, $element['#multiple'], $element['#required'], $options);
        // If the element isn't required and cardinality is more than 1, and there
        // are some options, and a "none" option.
        if (!$element['#required'] && !$element['#multiple']) {
          // Use a dummy instance in order to use theme('options_none');
          $dummy_instance['widget']['type'] = 'options_select';
          $options = array('_none' => theme('options_none', array('instance' => $dummy_instance))) + $options;
        }
      }

      $element_validate = array();
      if ($type != 'item') {
        // Re-use options widget element validation, to correctly transform
        // submitted values from field => delta to delta => field.
        // @see options_field_widget().
        $element_validate[] = 'options_field_widget_validate';
      }
      if ($hidden_selected_gids) {
        $element_validate[] = 'og_field_widget_element_validate';
      }


      $element += array(
        // Input should be TRUE only if there are groups that can be selected.
        '#input' => $type != 'item',
        '#type' => $type,
        '#options' => $options,
        '#default_value' => $default_values,
        '#attributes' => array('class' => array('group-audience')),
        '#disabled' => empty($options),
        '#value_key' => 'gid',
        '#element_validate' => $element_validate,
        '#properties' => $properties,
        // Add OG specific context.
        '#opt_group' => $opt_group,
        '#audience' => $audience,
        '#hidden_selected_gids' => $hidden_selected_gids,
      );

      $return = $element;
      break;
  }

  return $return;
}

/**
 * Validate handler; Re-add hidden selected group IDs if exist.
 */
function og_field_widget_element_validate($element, &$form_state) {
  // The hidden group IDs.
  $gids = $element['#hidden_selected_gids'];
  $field_name = $element['#field_name'];
  if (!empty($form_state['values'][$field_name][LANGUAGE_NONE])) {
    foreach ($form_state['values'][$field_name][LANGUAGE_NONE] as $key => $value) {
      // Add the selected group IDs.
      $gids[$value['gid']] = $value['gid'];
    }
  }

  $value = array();
  foreach ($gids as $gid) {
    $value[] = array('gid' => $gid);
  }
  // Set the new values.
  form_set_value($element, $value, $form_state);
}

/**
 * Implements hook_field_is_empty().
 */
function og_field_is_empty($item, $field) {
  return empty($item['gid']);
}

/**
 * Implements hook_field_attach_insert().
 */
function og_field_attach_insert($entity_type, $entity) {
  og_field_crud_group('insert', $entity_type, $entity);
}

/**
 * Implements hook_field_attach_update().
 */
function og_field_attach_update($entity_type, $entity) {
  og_field_crud_group('update', $entity_type, $entity);
}

/**
 * Implements hook_field_attach_delete().
 */
function og_field_attach_delete($entity_type, $entity) {
  og_field_crud_group('delete', $entity_type, $entity);
}

/**
 * Implements hook_field_insert().
 */
function og_field_insert($entity_type, $entity, $field, $instance, $langcode, &$items) {
  og_field_write('insert', $entity_type, $entity, $field, $instance, $langcode, $items);
}

/**
 * Implements hook_field_update().
 */
function og_field_update($entity_type, $entity, $field, $instance, $langcode, &$items) {
  og_field_write('update', $entity_type, $entity, $field, $instance, $langcode, $items);
}

 /**
 * Insert or update a field record.
 *
 * Since we can not change the field schema we must still populate the fields
 * with non-null values.
 *
 * @param $op
 *   The operation - "insert" or "update".
 */
function og_field_write($op, $entity_type, $entity, $field, $instance, $langcode, &$items) {
  if ($diff = og_field_save_group_audience_diff($entity_type, $entity, $field, $instance, $langcode, $items)) {
    foreach (array('insert', 'update', 'delete') as $op) {
      if (empty($diff[$op])) {
        continue;
      }

      foreach ($diff[$op] as $item) {
        $values = array(
          'entity_type' => $entity_type,
          'entity' => $entity,
          'gid' => $item['gid'],
          'type' => !empty($item['type']) ? $item['type'] : '',
        );
        call_user_func('og_membership_' . $op . '_on_field_save', $values);
      }
    }
  }

  // Clear the items, so they are not saved to the database.
  $items = array();
}



/**
 * Get the difference in group audience for a saved field.
 *
 * @return
 *   Array with all the differences, or an empty array if none found.
 */
function og_field_save_group_audience_diff($entity_type, $entity, $field, $instance, $langcode, $items) {
  $field_name = $field['field_name'];
  if (empty($entity->{$field_name})) {
    // The field is empty, so its a programmatic save of the entity.
    return NULL;
  }

  $return = array('insert' => array(), 'update' => array(), 'delete' => array());
  $wrapper = entity_metadata_wrapper($entity_type, $entity);

  $existing_gids = array();

  // Get the existing OG memberships.
  foreach ($wrapper->og_membership->value() as $og_membership) {
    $gid = $og_membership->gid;
    $item = array(
      'gid' => $og_membership->gid,
      'state' => $og_membership->state,
    );
    $existing_gids[$gid] = $item;
  }

  // Get the group membership from the field instance settings.
  $membership_type = $instance['widget']['settings']['membership_type'];

  $new_gids = array();
  foreach ($items as $item) {
    $item += array('type' => $membership_type, 'state' => OG_STATE_ACTIVE);
    $new_gids[$item['gid']] = $item;
  }

  foreach ($wrapper->og_membership->value() as $og_membership) {
    $gid = $og_membership->gid;
    $item = array(
      'gid' => $gid,
      'state' => $og_membership->state,
      'type' => $membership_type,
    );
    if (empty($new_gids[$gid]) && $membership_type == $og_membership->type) {
      $return['delete'][$gid] = $item;
    }
  }

  // Check if the membership is new, or updating an existing one.
  foreach ($new_gids as $gid => $item) {
    if (empty($existing_gids[$gid])) {
      $return['insert'][$gid] = $item;
    }
    elseif ($item['state'] != $existing_gids[$gid]['state']) {
      // This is an existing group. Update the group only if the state has
      // changed.
      $return['update'][$gid] = $item;
    }
  }
  return $return;
}

/**
 * Create a new group membership on entity save.
 *
 * @see og_entity_insert()
 * @see og_entity_update()
 */
function og_membership_insert_on_field_save($values = array()) {
  // Create a new group membership entity.
  list($id) = entity_extract_ids($values['entity_type'], $values['entity']);
  og_group($values['gid'], $values);
}

/**
 * Update an existing group membership on entity save.
 *
 * @see og_entity_update()
 */
function og_membership_update_on_field_save($values = array()) {
  // Update group membership entity.
  list($id) = entity_extract_ids($values['entity_type'], $values['entity']);
  og_group($values['gid'], $values);
}

/**
 * Delete an existing group membership on entity save.
 *
 * @see og_entity_update()
 */
function og_membership_delete_on_field_save($values = array()) {
  list($id) = entity_extract_ids($values['entity_type'], $values['entity']);
  og_ungroup($values['gid'], $values['entity_type'], $id);
}

/**
 * Implements hook_field_attach_form().
 *
 * Handle translated nodes that act as groups.
 */
function og_field_attach_form($entity_type, $entity, &$form, $form_state, $langcode) {
  if (!empty($form['#node_edit_form']) &&
    (og_is_group_type('node', $form['type']['#value']) || og_is_group_content_type('node', $form['type']['#value'])) &&
    (!empty($entity->tnid) && $entity->tnid != $entity->nid) || (!empty($_GET['translation']) && !empty($_GET['target']))) {

    // Iterate over OG fields that shouldn't be changed in node translation.
    foreach (og_fields_info() as $field_name => $info) {
      if (!empty($form[$field_name]) && $info['disable on node translate']) {
        // Prevent changing the group state on nodes that are translated.
        $description = t('You can not change "@label" field from a translated content.', array('@label' => $info['instance']['label']));

        $tnid = !empty($entity->tnid) ? $entity->tnid : $_GET['translation'];
        if (($node = node_load($tnid)) && node_access('update', $node)) {
          $description .= ' ' . t('Changing this field can only be done via <a href="@node">@title</a>.', array('@node' => url('node/' . $node->nid . '/edit'), '@title' => $node->title));
        }

        $form[$field_name][LANGUAGE_NONE]['#options'] = array();
        $form[$field_name][LANGUAGE_NONE]['#description'] = $description;
        $form[$field_name][LANGUAGE_NONE]['#disabled'] = TRUE;
        $form[$field_name][LANGUAGE_NONE]['#required'] = FALSE;

      }
    }
  }
}

/**
 * Validate handler; Re-add hidden selected group IDs if exist.
 */
function og_field_widget_form_validate($form, &$form_state) {
  if (empty($form[OG_AUDIENCE_FIELD][LANGUAGE_NONE]['#hidden_selected_gids'])) {
    return;
  }

  // The hidden group IDs.
  $gids = $form[OG_AUDIENCE_FIELD][LANGUAGE_NONE]['#hidden_selected_gids'];
  if (!empty($form_state['values'][OG_AUDIENCE_FIELD][LANGUAGE_NONE])) {
    foreach ($form_state['values'][OG_AUDIENCE_FIELD][LANGUAGE_NONE] as $key => $value) {
      // Add the selected group IDs.
      $gids[$value['gid']] = $value['gid'];
    }
  }

  $delta = 0;
  $value = array();
  foreach ($gids as $gid) {
    $value[LANGUAGE_NONE][$delta]['gid'] = $gid;
    ++$delta;
  }
  // Set the new values.
  form_set_value($form[OG_AUDIENCE_FIELD], $value, $form_state);
}

/**
 * Create update or delete a group, based on the field CRUD.
 *
 * TODO: Remove this an move to hook_entity_presave().
 *
 * @see og_field_attach_insert().
 * @see og_field_attach_update().
 * @see og_field_attach_delete().
 */
function og_field_crud_group($op, $entity_type, $entity) {
  $property = OG_GROUP_FIELD;

  // If the entity is a translated node, we can return early, as all operations
  // happen on the source node.
  if ($entity_type == 'node'
    && ((!empty($entity->tnid) && $entity->tnid != $entity->nid) || !empty($entity->translation_source->tnid))
    && module_exists('translation')) {
    return;
  }

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
            $values = array('entity' => $account);
            og_group($group->gid, $values);
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
          og_roles_override($group->gid);
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
function og_field_audience_options(&$opt_group = FALSE, $account = NULL) {
  $return = &drupal_static(__FUNCTION__, array());

  if (!empty($return)) {
    return $return;
  }

  if (empty($account)) {
    global $user;

    $account = clone($user);
  }

  //Initialize values.
  $return = array('content groups' => array(), 'other groups' => array());

  $content_groups = og_get_entity_groups('user', $account);
  $return['content groups'] = $content_groups;

  // Get all other groups.
  $all_groups = og_get_all_group();
  $return['other groups'] = array_diff($all_groups, $content_groups);

  // Allow other modules to change the audience options. While it can be done
  // via hook_form_alter(), it will require other modules to know too much of
  // the internal work.
  drupal_alter('og_audience_options', $return, $opt_group, $account);

  return $return;
}

/**
 * Implements hook_field_validate().
 *
 * FIXME: Adapt this function to OG.
 *
 * Possible error codes:
 * - 'invalid_nid': nid is not valid for the field (not a valid node id, or the node is not referenceable).
 */
function __og_field_validate($entity_type, $entity, $field, $instance, $langcode, $items, &$errors) {
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
