<?php
// $Id$

/**
 * @file
 * Field module functionality for the Organic groups UI module.
 */

/*******************************************************************************
 * Hook implementations
 ******************************************************************************/

/**
 * Implement hook_field_info().
 */
function og_ui_field_info() {
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
function og_ui_field_formatter_info() {
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
function og_ui_field_schema($field) {
  $columns = array(
    'value' => array(
      'type' => 'float',
      'unsigned' => TRUE,
      'not null' => FALSE,
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
function og_ui_field_widget_info() {
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
function og_ui_field_widget(&$form, &$form_state, $field, $instance, $langcode, $items, $delta = NULL) {
  $element = array(
    '#type' => $instance['widget']['type'],
    '#default_value' => !empty($items) ? $items : array(),
  );
  return $element;
}

/**
 * Implement hook_field_is_empty().
 */
function og_ui_field_is_empty($item, $field) {
  return empty($item['value']);
}

/**
 * Implement hook_field_insert().
 */
function og_ui_field_insert($obj_type, $object, $field, $instance, $langcode, &$items) {
  if ($field['type'] == 'og') {
    // TODO: What we do with other objects?
    if ($obj_type == 'node') {
      // Create new associations.
      foreach ($items as $value) {
        og_set_association($value['value'], $object->nid, $obj_type);
      }
    }

  }
}

/**
 * Implement hook_field_update().
 */
function og_ui_field_update($obj_type, $object, $field, $instance, $langcode, &$items) {
  if ($field['type'] == 'og') {
    if ($obj_type == 'node') {
      // Delete association of object.
      og_delete_association_by_content($object->nid, $obj_type);

      // Create new associations.
      foreach ($items as $value) {
        og_set_association($value['value'], $object->nid, $obj_type);
      }
    }
  }
}

/**
 * Implement hook_field_delete().
 */
function hook_field_delete($obj_type, $object, $field, $instance, $langcode, &$items) {
  if ($field['type'] == 'og') {
    if ($obj_type == 'node') {
      // Delete association of object.
      og_delete_association_by_content($object->nid, $obj_type);
    }
  }
}

/**
 * Implement hook_field_delete_instance()
 */
function og_ui_field_delete_instance($instance) {
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