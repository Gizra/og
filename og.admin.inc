<?php
// $Id: og.admin.inc,v 1.15.4.2 2009/06/22 15:59:27 weitzman Exp $

/**
 * @file
 * Admin settings for the organic groups UI module.
 */

/**
 * Menu callback; Organic groups settings form.
 */
function og_admin_settings() {
  $form['og_settings'] = _og_content_types();
  
  $form['og_settings']['group_details'] = array(
    '#type' => 'fieldset', 
    '#title' => t('Group details'),
    '#description' => t('The default behavior of new groups.'),  
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE
  );
  // Groups directory visibility
  $options = array(
    t("New groups don't appear in the groups directory. Administrators control the directory exclusively."),
    t('New groups always appear in the groups directory.'),
    t('Group creator chooses whether her group appears in the directory. Defaults to <em>in directory</em>.'),
    t('Group creator chooses whether her group appears in the directory. Defaults to <em>not in directory</em>.'),
  );
  $form['og_settings']['group_details']['og_visibility_directory'] = array(
    '#type' => 'radios', 
    '#title' => t('Groups directory control'), 
    '#default_value' => variable_get('og_visibility_directory', OG_DIRECTORY_CHOOSE_TRUE), 
    '#description' =>t('Site admins always see the checkbox for adding a group to the <em>groups directory</em>. Note that changing this setting has no effect on existing posts. Re-save those posts to acquire this new setting.'), 
    '#options' => $options
  );
  
  // Groups registration visibility.
  $options = array(
    t("New groups don't appear on the registration form. Administrators control the form exclusively."),
    t('New groups always appear on the registration form.'),
    t('Group creator chooses whether her group appears on the registration form. Defaults to <em>on form</em>.'),
    t('Group creator chooses whether her group appears on the registration form. Defaults to <em>not on form</em>.'),
  );
  $form['og_settings']['group_details']['og_visibility_registration'] = array(
    '#type' => 'radios', 
    '#title' => t('Registration form control'), 
    '#default_value' => variable_get('og_visibility_registration', OG_REGISTRATION_CHOOSE_FALSE), 
    '#description' =>t('OG admins always see the checkbox for adding a group to the <em>registration form</em>. Note that changing this setting has no effect on existing posts. Re-save those posts to acquire this new setting.'), 
    '#options' => $options
  );

  // Audience form element
  $form['og_settings']['group_details']['og_audience'] = array(
    '#type' => 'checkbox', 
    '#title' => t('Audience form element'), 
    '#default_value' => variable_get('og_audience', TRUE), 
    '#description' => t('Show a form element for the user to choose the "Audience" of the content. This enables the member to place her post into multiple groups. If unchecked, simplify the user interface by omitting the audience form element and assume the user wants to post into the current group. This simplification only applies to new nodes, and not to edits of existing content. Group administrators always see the audience form element.')
  );
  
  // audience required
  $options = array(t('Optional'), t('Required'));
  $form['og_settings']['group_details']['og_audience_required'] = array(
    '#type' => 'radios', 
    '#title' => t('Audience required'), 
    '#default_value' => variable_get('og_audience_required', 0), 
    '#options' => $options, 
    '#description' => t('Do you require that all (non administrator) posts be affiliated with a group? Note that changing this setting will affect existing posts when they are edited.')
  );
  unset($options);

  // Messages fieldset.
  // TODO: Move message templates to Rules?

  return system_settings_form($form);
}


/**
 * Return a table element with the content types and their organic groups type.
 */
function _og_content_types() {
  $form = array();
  
  // Content types.
  $is_configured = count(og_get_node_type_by_og_type(array('group'))) && count(og_get_node_type_by_og_type(array('group post')));
  if (!$is_configured) {
    form_set_error('content_types_table', t('You must designate at least one content type to act as a group node and another as a group post. <a href="!create">Create new content type</a> if needed.', array('!create' => url('admin/content/types/add', array('query' => drupal_get_destination())))));
  }  
  
  $form['content_types'] = array(
    '#type' => 'fieldset', 
    '#title' => t('Content types'),
    '#description' => t('You may <a href="@content-types-add">create new content types</a> as needed. Note that you may have multiple types acting as groups and multiple types are allowed as group posts.', array('@content-types-add' => url('admin/content/types/add', array('query' => drupal_get_destination())))),
    '#collapsible' => TRUE, 
    '#collapsed' => $is_configured,
  );
  
  $og_types = og_get_node_type_by_og_type();
  $node_types = node_type_get_types();
    
  $header = array(t('Type'), t('Usage'), t('Operations'));
  $rows = array();
  
  foreach ($og_types as $type => $usage) {
    $type_url = str_replace('_', '-', $type);
    $rows[] = array(
      $node_types[$type]->name,
      $usage,
      l(t('Edit'), "admin/content/node-type/$type_url", array('query' => drupal_get_destination())),      
    );
  }
  $form['content_types']['content_types_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows
  );

  return $form;
  
}
