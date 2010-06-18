<?php
// $Id:$

/**
 * @file
 * Admin settings for the Group views module.
 */

function group_views_settings() {
  $form = array();

  $form['group_views_default'] = array(
    '#type' => 'select',
    '#title' => t('Group views default'),
    '#options' => group_views_field_allowed_values(FALSE),
    '#default_value' => variable_get('group_views_global_view', 'group_nodes'),
  );

  return system_settings_form($form);
}