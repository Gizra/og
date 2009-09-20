<?php
// $Id: og.admin.inc,v 1.15.4.2 2009/06/22 15:59:27 weitzman Exp $

/**
 * @file
 * Admin settings for the organic groups module.
 */

/**
 * Menu callback: administer roles.
 *
 * @ingroup forms
 * @see og_user_admin_role_validate()
 * @see og_user_admin_role_submit()
 * @see theme_og_user_admin_new_role()
 */
function og_user_admin_role($form, $form_state, $node) {
  if (og_is_group_type($node->type)) {
    $rid = arg(5);
    if ($rid) {
      if ($rid == DRUPAL_ANONYMOUS_RID || $rid == DRUPAL_AUTHENTICATED_RID) {
        drupal_goto('admin/config/people/roles');
      }
      // Display the edit role form.
      $role = db_query('SELECT * FROM {og_role} WHERE rid = :rid', array(':rid' => $rid))->fetchObject();
      $form['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Role name'),
        '#default_value' => $role->name,
        '#size' => 30,
        '#required' => TRUE,
        '#maxlength' => 64,
        '#description' => t('The name for this role. Example: "moderator", "editorial board", "site architect".'),
      );
      $form['rid'] = array(
        '#type' => 'value',
        '#value' => $rid,
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save role'),
      );
      $form['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete role'),
      );
    }
    else {
      $form['nid'] = array('#type' => 'value', '#value' => $node->nid);

      $form['name'] = array(
        '#type' => 'textfield',
        '#size' => 32,
        '#maxlength' => 64,
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Add role'),
      );
      $form['#submit'][] = 'og_user_admin_role_submit';
      $form['#validate'][] = 'og_user_admin_role_validate';
    }
    return $form;
  }
  else {
    // Not a group node.
    drupal_not_found();
    exit;
  }
}

function og_user_admin_role_validate($form, &$form_state) {
  if ($form_state['values']['name']) {
    if ($form_state['values']['op'] == t('Save role')) {
      $role = og_user_role_load($form_state['values']['name']);
      if ($role && $role->rid != $form_state['values']['rid']) {    
        form_set_error('name', t('The role name %name already exists. Please choose another role name.', array('%name' => $form_state['values']['name'])));
      }
    }
    elseif ($form_state['values']['op'] == t('Add role')) {
      if (og_user_role_load($form_state['values']['name'])) {
        form_set_error('name', t('The role name %name already exists. Please choose another role name.', array('%name' => $form_state['values']['name'])));
      }
    }
  }
  else {
    form_set_error('name', t('You must specify a valid role name.'));
  }
}

function og_user_admin_role_submit($form, &$form_state) {
  $role = (object)$form_state['values'];
  if ($form_state['values']['op'] == t('Save role')) {
    og_user_role_save($role);
    drupal_set_message(t('The role has been renamed.'));
  }
  elseif ($form_state['values']['op'] == t('Delete role')) {
    og_user_role_delete($form_state['values']['rid']);
    drupal_set_message(t('The role has been deleted.'));
  }
  elseif ($form_state['values']['op'] == t('Add role')) {
    og_user_role_save($role);
    drupal_set_message(t('The role has been added.'));
  }
  $form_state['redirect'] = 'og/' . $form_state['values']['nid'] .'/admin/people/roles';
  return;
}

/**
 * Theme the new role form.
 *
 * @ingroup themeable
 */
function theme_og_user_admin_new_role($form) {
  $header = array(t('Name'), array('data' => t('Operations'), 'colspan' => 2));
  foreach (og_user_roles($form['nid']['#value']) as $rid => $name) {
    $edit_permissions = l(t('edit permissions'), 'admin/config/people/permissions/' . $rid);
    if (!in_array($rid, array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
      $rows[] = array($name, l(t('edit role'), 'admin/config/people/roles/edit/' . $rid), $edit_permissions);
    }
    else {
      $rows[] = array($name, t('locked'), $edit_permissions);
    }
  }
  $rows[] = array(drupal_render($form['name']), array('data' => drupal_render($form['submit']), 'colspan' => 2));

  $output = drupal_render_children($form);
  $output .= theme('table', $header, $rows);

  return $output;
}


/**
 * Menu callback: administer permissions.
 *
 * @ingroup forms
 * @see user_admin_permissions_submit()
 * @see theme_user_admin_permissions()
 */
function og_user_admin_permissions($form, $form_state, $rid = NULL) {

  // Retrieve role names for columns.
  $role_names = og_user_roles();
  if (is_numeric($rid)) {
    $role_names = array($rid => $role_names[$rid]);
  }
  // Fetch permissions for all roles or the one selected role.
  $role_permissions = og_user_role_permissions($role_names);

  // Store $role_names for use when saving the data.
  $form['role_names'] = array(
    '#type' => 'value',
    '#value' => $role_names,
  );
  // Render role/permission overview:
  $options = array();
  $hide_descriptions = !system_admin_compact_mode();
  foreach (module_implements('og_permission') as $module) {
    if ($permissions = module_invoke($module, 'og_permission')) {
      $form['permission'][] = array('#markup' => $module, '#id' => $module);
      
      foreach ($permissions as $perm => $perm_item) {
        $options[$perm] = '';
        $form['permission'][$perm] = array(
          '#type' => 'item',
          '#markup' => $perm_item['title'],
          '#description' => $hide_descriptions ? $perm_item['description'] : NULL,
        );
        foreach ($role_names as $rid => $name) {
          // Builds arrays for checked boxes for each role
          if (isset($role_permissions[$rid][$perm])) {
            $status[$rid][] = $perm;
          }
        }
      }
    }
  }

  // Have to build checkboxes here after checkbox arrays are built
  foreach ($role_names as $rid => $name) {
    $form['checkboxes'][$rid] = array('#type' => 'checkboxes', '#options' => $options, '#default_value' => isset($status[$rid]) ? $status[$rid] : array());
    $form['role_names'][$rid] = array('#markup' => $name, '#tree' => TRUE);
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save permissions'));

  $form['#attached']['js'][] = drupal_get_path('module', 'user') . '/user.permissions.js';

  return $form;
}

/**
 * Save permissions selected on the administer permissions page.
 *
 * @see og_user_admin_permissions()
 */
function og_user_admin_permissions_submit($form, &$form_state) {
  foreach ($form_state['values']['role_names'] as $rid => $name) {
    og_user_role_change_permissions($rid, $form_state['values'][$rid]);
  }

  drupal_set_message(t('The changes have been saved.'));

  // Clear the cached pages and blocks.
  cache_clear_all();
}


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
