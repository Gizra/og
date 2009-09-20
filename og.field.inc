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
      'label' => t('OG default list'),
      'field types' => array('og'),
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
function og_field_widget(&$form, &$form_state, $field, $instance, $langcode, $items, $delta = NULL) {
  $default_values = array();
  if (!empty($items)) {
    foreach ($items as $item) {
      $default_values[$item['value']] = $item['value'];
    }
  }
  $element = array(
    '#type' => $instance['widget']['type'],
    '#default_value' => $default_values,
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
  // Allow other modules to alter the data.
  foreach ($items as &$item) {
    $item['state'] = !empty($item['state']) ? $item['state'] : '';
    $item['data'] = !empty($item['data']) ? $item['data'] : array();
    $alter = array('state' => $item['state'], 'data' => $item['data']);
    drupal_alter('og_field_insert', $alter, $obj_type, $object, $field, $instance, $langcode, $items);
    $item['state'] = $alter['state'];
    $item['data'] = serialize($alter['data']);
  }
}

/**
 * Implement hook_field_update().
 */
function og_field_update($obj_type, $object, $field, $instance, $langcode, &$items) {
  foreach ($items as &$item) {
    //  FIXME: pass the data and state values.
    $item['state'] = '';
    $item['data'] = array();

    $alter = array('state' => $item['state'], 'data' => $item['data']);

    // Allow other modules to alter the data.
    drupal_alter('og_field_update', $alter, $obj_type, $object, $field, $instance, $langcode, $items);
    $item['state'] = $alter['state'];
    $item['data'] = serialize($alter['data']);
  }
}


/*******************************************************************************
 * Helper functions
 ******************************************************************************/

/**
 * Add organic groups audience field to fieldable enteties.
 *
 * @param $bundle
 *   The bundle name.
 */
function og_set_og_audince_field($bundle) {
  $field = field_info_field('og_audience');
  if (empty($field)) {
    $field = array(
      'field_name' => 'og_audience',
      'type' => 'og',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      // TODO: Lock only delete.
      // 'locked' => TRUE,
    );
    $field = field_create_field($field);
  }

  $instance = field_info_instance('og_audience', $bundle);
  if (empty($instance)) {
    $instance = array(
      'field_name' => 'og_audience',
      'bundle' => $bundle,
      'label' => t('Groups audience'),
      'widget_type' => 'og_audience',
      'display' => array(
        'full' => array(
          'label' => 'above',
          'type' => 'og_list_default',
        ),
        'teaser' => array(
          'label' => 'above',
          'type' => 'og_list_default',
        ),
      ),
    );
    field_create_instance($instance);
  }
}

/**
 * Return the selective state of a group node.
 *
 * @param $node
 *   The group node.
 */
function og_get_group_selective($node) {
  $langcode = FIELD_LANGUAGE_NONE;
  return $node->{OG_SELECTIVE_FIELD}[$langcode][0]['value'];
}

/**
 * Add fields to group post node types.
 *
 * @param $node_types
 *   Array of the node types that need to have the field attached to.
 * @return
 *   The node types that have been changed, as some might already have the
 *   fields attached to them.
 */
function og_group_type_set_fields($node_types = array()) {
  foreach ($node_types as $type) {

    $field = field_info_field(OG_SELECTIVE_FIELD);
    if (empty($field)) {
      $field = array(
        'field_name' => OG_SELECTIVE_FIELD,
        'type' => 'list_text',
        // TODO: Lock only delete.
        // 'locked' => TRUE,
        'settings' => array('allowed_values_function' => 'og_get_og_selective'),
      );
      $field = field_create_field($field);
    }

    $instance = field_info_instance(OG_SELECTIVE_FIELD, $type);
    if (empty($instance)) {
      $instance = array(
        'field_name' => OG_SELECTIVE_FIELD,
        'bundle' => $type,
        'label' => t('Groups selective'),
        'required' => TRUE,
        'widget_type' => 'options_select',
        'display' => array(
          'full' => array(
            'label' => 'above',
            'type' => 'list_default',
          ),
          'teaser' => array(
            'label' => 'above',
            'type' => 'list_default',
          ),
        ),
      );
      field_create_instance($instance);
    }
  }
}

/**
 * Add fields to group post node types.
 *
 * @param $node_types
 *   Array of the node types that need to have the field attached to.
 * @return
 *   TODO: The node types that have been changed, as some might already have the
 *   fields attached to them.
 */
function og_group_post_type_set_fields($node_types = array()) {
  foreach ($node_types as $type) {
    og_set_og_audince_field($type);
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
function theme_field_formatter_og_list_default($element) {
  $field = field_info_field($element['#field_name']);
  $options = og_allowed_values($field);
  // Flatten the options. We don't use array_merge() because we want to keep the
  // index key, which is the group node ID.
  $options = $options['content groups'] + $options['other groups'];
  // Return the group's link.
  $return = l($options[$element['#item']['value']], 'node/'. $element['#item']['value']);
  return $return;
}

