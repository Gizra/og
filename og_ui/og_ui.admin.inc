<?php
// $Id: og.admin.inc,v 1.15.4.2 2009/06/22 15:59:27 weitzman Exp $

/**
 * @file
 * Admin settings for the Organic groups module.
 */

/**
 * Provide an overview of the administrator menu items.
 *
 * @param $node
 *   The group node.
 */
function og_ui_group_admin_overview($obj_type, $oid) {
  og_set_breadcrumb($obj_type, $oid);

  $items = og_ui_get_group_admin();

  foreach ($items as &$item) {
    // Re-format the URL.
    $item['href'] = "og/$obj_type/$oid/" . $item['href'];
    // Imitate the menu_get_item() content, so we can pass it to
    // theme_admin_block_content(), so we add the localized_options key.
    $item['localized_options'] = array();
  }

  $form['system_compact_link'] = array('#markup' => theme('system_compact_link'));
  $form['admin_block_content'] = array('#markup' => theme('admin_block_content', array('content' => $items)));

  return $form;
}

/**
 * Add users to group form.
 */
function og_ui_add_users($form, &$form_state, $obj_type, $oid) {
  og_set_breadcrumb($obj_type, $oid, array(l(t('Group'), "$obj_type/$oid/og")));
  if ($group = og_get_group($obj_type, $oid)) {
    $label = check_plain(og_entity_get_label($group->obj_type, $group->oid));

    $form['og_user'] = array(
      '#type' => 'fieldset',
      '#title' => t('Add a group member to %group', array('%group' => $label)),
    );
    $form['og_user']['og_add_user'] = array(
      '#type' => 'textfield',
      '#title' => t('User name'),
      '#autocomplete_path' => 'user/autocomplete',
    );
    $form['og_user']['og_add_user_state'] = array(
      '#type' => 'select',
      '#title' => t('Membership state'),
      '#options' => array(
        OG_STATE_ACTIVE => t('Active'),
        OG_STATE_PENDING => t('Pending'),
        OG_STATE_BLOCKED => t('Blocked'),
      ),
      '#description' => t('Select the state of the membership.'),
    );
    $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Add users'));
    $form['group'] = array('#type' => 'value', '#value' => $group);
    return $form;
  }
  // Not a valid group node.
  drupal_not_found();
}

/**
 * Validate handler; Add users to group.
 */
function og_ui_add_users_validate($form, &$form_state) {
  $account = user_load_by_name($form_state['values']['og_add_user']);
  if (!$account) {
    form_set_error('og_add_user', t('You have entered an invalid user name.'));
  }
}

/**
 * Submit handler; Add users to group.
 */
function og_ui_add_users_submit($form, &$form_state) {
  $account = user_load_by_name($form_state['values']['og_add_user']);
  $group = array(
    'gid' => $form_state['values']['group']->gid,
    'state' => $form_state['values']['og_add_user_state'],
  );

  og_subscribe_user(array($group), $account);
  drupal_set_message(t('%user has been added to group.', array('%user' => $account->name)));
}

/**
 * Form builder; OG user administration page.
 *
 * @ingroup forms
 * @see og_user_admin_account_validate()
 * @see og_user_admin_account_submit()
 */
function og_ui_user_admin_account($form, $form_state, $obj_type, $oid) {
  og_set_breadcrumb($obj_type, $oid, array(l(t('Group'), "$obj_type/$oid/og")));
  $group = og_get_group($obj_type, $oid);
  $header = array(
    'username' => array('data' => t('Username')),
    'status' => array('data' => t('Status')),
    'roles' => array('data' => t('Roles')),
  );

  $form['group'] = array('#type' => 'value', '#value' => $group);
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#prefix' => '<div class="container-inline">',
    '#suffix' => '</div>',
  );
  $options = array();
  foreach (module_implements('og_user_operations') as $module) {
    $result = call_user_func($module . '_og_user_operations', array(), array('group' => $group));
    foreach ($result as $operation => $array) {
      $options[$operation] = $array['label'];
    }
  }

  $form['options']['operation'] = array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => 'unblock',
  );

  $options = array();
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
  );

  $status = array('blocked' => t('blocked'), 'pending' => t('pending'), 'active' => t('active'));
  $roles = array_flip(og_get_group_roles($group->gid));
  $accounts = array();
  // Get the group real object, so we can check the user ID of the object.
  $object = current(entity_load($obj_type, array($group->oid)));

  foreach (og_get_group_users($group->gid, array()) as $account) {
    // Don't allow editing of the group manager.
    if (empty($object->uid) || $account->uid != $object->uid) {
      $accounts[$account->uid] = '';
      $users_roles = array();
      foreach (og_get_user_roles($group->gid, $account->uid) as $rid) {
        $users_roles[] = $roles[$rid];
      }
      asort($users_roles);

      $options[$account->uid] = array(
        'username' => theme('username', array('account' => $account)),
        'status' =>  $status[$account->state],
        'roles' => theme('item_list', array('items' => $users_roles)),
      );

    }
  }
  $form['accounts'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => !empty($object->uid) ? t('No people available apart of the group manager.') : t('No people available.'),
  );
  $form['pager'] = array('#markup' => theme('pager', array('tags' => NULL)));

  return $form;
}


function og_ui_user_admin_account_validate($form, &$form_state) {
  $form_state['values']['accounts'] = array_filter($form_state['values']['accounts']);
  if (count($form_state['values']['accounts']) == 0) {
    form_set_error('', t('No users selected.'));
  }
}

/**
 * Submit the user administration update form.
 */
function og_ui_user_admin_account_submit($form, &$form_state) {
  $group = $form_state['values']['group'];

  $operations = module_invoke_all('og_user_operations', $form, $form_state + array('group' => $group));
  $operation = $operations[$form_state['values']['operation']];
  // Filter out unchecked accounts.
  $accounts = array_filter($form_state['values']['accounts']);

  if ($function = $operation['callback']) {
    // Add in callback arguments if present.
    if (isset($operation['callback arguments'])) {
      $args = array_merge(array($accounts), $operation['callback arguments']);
    }
    else {
      $args = array($accounts);
    }
    call_user_func_array($function, array('gid' => $group->gid) + $args);

    drupal_set_message(t('The update has been performed.'));
  }
}

/**
 * Menu callback: administer roles.
 *
 * @ingroup forms
 * @see og_user_admin_role_validate()
 * @see og_user_admin_role_submit()
 * @see theme_og_user_admin_new_role()
 */
function og_ui_user_admin_role($form, $form_state, $obj_type, $oid) {
  if ($group = og_get_group($obj_type, $oid)) {
    $form['group'] = array('#type' => 'value', '#value' => $group);

    $item = menu_get_item();
    // Get the role ID from the URL.
    $rid = !empty($item['map'][7]) ? $item['map'][7] : 0;

    if ($rid) {
      og_set_breadcrumb($obj_type, $oid, array(l(t('Group'), "$obj_type/$oid/og")), l(t('Roles'), "og/$obj_type/$oid/admin/people/roles"));

      $og_roles = og_get_group_default_roles($group->gid);
      if (in_array($rid, $og_roles)) {
        drupal_goto("og/$obj_type, $oid/admin/people/roles");
      }
      // Display the edit role form.
      $role = og_user_role_load($rid);
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
      $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save role'),
      );
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete role'),
      );
    }
    else {
      og_set_breadcrumb($obj_type, $oid, array(l(t('Group'), "$obj_type/$oid/og")));
      $form['name'] = array(
        '#type' => 'textfield',
        '#size' => 32,
        '#maxlength' => 64,
      );
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Add role'),
      );
      $form['#submit'][] = 'og_ui_user_admin_role_submit';
      $form['#validate'][] = 'og_ui_user_admin_role_validate';
    }
    return $form;
  }
  else {
    // Not a group node.
    drupal_not_found();
    exit;
  }
}

function og_ui_user_admin_role_validate($form, &$form_state) {
  if ($form_state['values']['name']) {
    $roles = og_user_roles($form_state['values']['group']->gid);
    if (in_array($form_state['values']['name'], $roles)) {
      form_set_error('name', t('The role name %name already exists. Please choose another role name.', array('%name' => $form_state['values']['name'])));
    }
  }
  else {
    form_set_error('name', t('You must specify a valid role name.'));
  }
}

function og_ui_user_admin_role_submit($form, &$form_state) {
  $group = $form_state['values']['group'];
  $role->gid = $group->gid;
  $role->name = $form_state['values']['name'];
  if (!empty($form_state['values']['rid'])) {
    $role->rid= $form_state['values']['rid'];
  }
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
  $form_state['redirect'] = 'og/' . $group->obj_type . '/' . $group->oid . '/admin/people/roles';
  return;
}

/**
 * Theme the new role form.
 *
 * @ingroup themeable
 */
function theme_og_ui_user_admin_new_role($variables) {
  $form = $variables['form'];
  $header = array(t('Name'), array('data' => t('Operations'), 'colspan' => 2));
  // The group object.
  $group = $form['group']['#value'];

  $og_roles = og_get_group_default_roles($group->gid);

  foreach (og_user_roles($group->gid) as $rid => $name) {
    $edit_permissions = l(t('edit permissions'), 'og/' . $group->obj_type .'/' . $group->oid . '/admin/people/permissions/' . $rid);
    if (!in_array($rid, $og_roles)) {
      $rows[] = array($name, l(t('edit role'), 'og/' . $group->obj_type .'/' . $group->oid . '/admin/people/roles/edit/' . $rid), $edit_permissions);
    }
    else {
      $rows[] = array($name, t('locked'), $edit_permissions);
    }
  }

  $rows[] = array(array('data' => drupal_render($form['name']) . drupal_render($form['submit']), 'colspan' => 3, 'class' => 'edit-name'));

  $output = drupal_render_children($form);
  $output .= theme('table', array('header' => $header, 'rows' => $rows));

  return $output;
}


/**
 * Menu callback: administer permissions.
 *
 * @ingroup forms
 * @see user_admin_permissions_submit()
 * @see theme_user_admin_permissions()
 */
function og_ui_user_admin_permissions($form, $form_state, $obj_type = NULL, $oid = NULL, $rid = NULL) {
  // If no node object is provided then the node ID is 0, which means this
  // is the default permissions settings.
  $group = !empty($oid) ? og_get_group($obj_type, $oid) : array();
  $form['group'] = array('#type' => 'value', '#value' => $group);

  if (!empty($group)) {
    $gid =  $group->gid;
    og_set_breadcrumb($obj_type, $oid, array(l(t('Group'), "$obj_type/$oid/og")));
  }
  else {
    $gid = 0;
  }

  // Retrieve role names for columns.
  $role_names = og_user_roles($gid);
  if (!empty($rid)) {
    $role_names = array($rid => $role_names[$rid]);
  }
  // Fetch permissions for all roles or the one selected role.
  $role_permissions = og_user_role_permissions($role_names);

  if (empty($group)) {
    // Add the 'bulk update' checkbox to the user roles.
    $role_names += array(0 => t('bulk update'));
  }

  // Store $role_names for use when saving the data.
  $form['role_names'] = array(
    '#type' => 'value',
    '#value' => $role_names,
  );
  // Render role/permission overview:
  $options = array();
  $hide_descriptions = !system_admin_compact_mode();
  foreach (og_get_permissions() as $module => $permissions) {
    $form['permission'][] = array('#markup' => $module != 'administer' ? $module : t('administer group admins privileges'), '#id' => $module);
    foreach ($permissions as $perm => $perm_item) {
      $access = !empty($group) ? og_user_access($gid, 'show ' . $perm) : TRUE;
      if ($access) {
        $options[$perm] = '';
        $form['permission'][$perm] = array(
          '#type' => 'item',
          '#markup' => $perm_item['title'],
          '#description' => $hide_descriptions ? $perm_item['description'] : '',
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

  if (empty($group)) {
    $form['#theme'] = array('og_ui_user_admin_permissions');
  }
  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save permissions'),
    '#submit' => array('og_ui_user_admin_permissions_submit'),
  );
  $form['#after_build'] = array('og_ui_user_admin_permissions_after_build');

  return $form;
}

/**
 * Helper function to disable the checkboxes according to their 'roles' state.
 *
 * Every permission can decdeclate to which roles it applies, as some don't make
 * sense if applied to anonymous and authenticated user (e.g. subscribe to group
 * should appear only to anonymous members).
 */
function og_ui_user_admin_permissions_after_build($element) {
  // Authenticated roles.
  $gid = !empty($element['group']['#value']->gid) ? $element['group']['#value']->gid : 0;
  $auth_rids = array_flip(og_user_roles($gid));
  // Remove the anonymous member from the authenticated roles.
  $anon_rid = array_shift($auth_rids);

  foreach (og_get_permissions() as $module_perms) {
    foreach ($module_perms as $key => $perm) {
      $perm['roles'] = drupal_map_assoc($perm['roles']);

      if (empty($perm['roles']) || empty($perm['roles'][OG_ANONYMOUS_ROLE])) {
        $element['checkboxes'][$anon_rid][$key]['#attributes']['disabled'] = TRUE;
      }
      if (empty($perm['roles']) || empty($perm['roles'][OG_AUTHENTICATED_ROLE])) {
        foreach ($auth_rids as $auth_rid) {
          $element['checkboxes'][$auth_rid][$key]['#attributes']['disabled'] = TRUE;
        }
      }
    }
  }

  return $element;
}
/**
 * Save permissions selected on the administer permissions page.
 *
 * @see og_user_admin_permissions()
 */
function og_ui_user_admin_permissions_submit($form, &$form_state) {
  foreach ($form_state['values']['role_names'] as $rid => $name) {
    if (!empty($rid)) {
      og_user_role_change_permissions($rid, $form_state['values'][$rid]);
    }
    else {
      // Bulk update existing groups.
      $roles = array(
        OG_ANONYMOUS_DEFAULT_RID => $form_state['values'][OG_ANONYMOUS_DEFAULT_RID],
        OG_AUTHENTICATED_DEFAULT_RID => $form_state['values'][OG_AUTHENTICATED_DEFAULT_RID],
        OG_ADMINISTRATOR_DEFAULT_RID => $form_state['values'][OG_ADMINISTRATOR_DEFAULT_RID],
      );
      if ($count = og_permissions_bulk_update($roles, $form_state['values'][$rid])) {
        drupal_set_message(format_plural($count, 'Group was updated', '@count group were updated.', array('@count' =>$count)));
      }

    }
  }

  drupal_set_message(t('The changes have been saved.'));
}


/**
 * Theme the administer permissions page.
 *
 * @ingroup themeable
 */
function theme_og_ui_user_admin_permissions($variables) {
  $form = $variables['form'];
  $gid = !empty($form['group']['#value']->gid) ? $form['group']['#value']->gid : 0;
  $roles = og_user_roles($gid);

  if (empty($gid)) {
    // Add the 'bulk update' checkbox to the user roles.
    $roles += array(0 => t('bulk update'));
  }
  foreach (element_children($form['permission']) as $key) {
    $row = array();
    // Module name.
    if (is_numeric($key)) {
      $row[] = array('data' => drupal_render($form['permission'][$key]), 'class' => array('module'), 'id' => 'module-' . $form['permission'][$key]['#id'], 'colspan' => count($form['role_names']['#value']) + 1);
    }
    else {
      // Permission row.
      $row[] = array(
        'data' => drupal_render($form['permission'][$key]),
        'class' => array('permission'),
      );
      foreach (element_children($form['checkboxes']) as $rid) {
        $row[] = array('data' => drupal_render($form['checkboxes'][$rid][$key]), 'class' => array('checkbox'), 'title' => $roles[$rid] . ' : ' . t($key));
      }
    }
    $rows[] = $row;
  }
  $header[] = (t('Permission'));
  foreach (element_children($form['role_names']) as $rid) {
    $header[] = array('data' => drupal_render($form['role_names'][$rid]), 'class' => array('checkbox'));
  }
  $output = theme('system_compact_link');
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'permissions')));
  $output .= drupal_render_children($form);
  return $output;
}


/**
 * Groups permissions on default settings form.
 *
 * Allow site admin to set which permissions are available on a per group basis.
 */
function og_ui_admin_settings() {
  $form = array();
  $options = array();

  $form['og_group'] = array(
    '#type' => 'fieldset',
    '#title' => t('Group type'),
  );
  $form['og_group']['og_group'] = _og_admin_fields_overview('group');

  $form['og_group_content'] = array(
    '#type' => 'fieldset',
    '#title' => t('Group post type'),
  );
  $form['og_group_content']['og_group_content'] = _og_admin_fields_overview('group content');

  $form['actions'] = array('#type' => 'container', '#attributes' => array('class' => array('form-actions')));
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add group and group content fields to bundles'),
    '#submit' => array('og_ui_admin_settings_submit'),
  );

  return $form;
}

/**
 * Helper function to create tables for group and group content fields.
 *
 * @param $group_type
 * @return unknown_type
 */
function _og_admin_fields_overview($group_type = 'group') {
  $form = array();
  $options = $status = array();

  $og_fields = og_get_fields();

  foreach(field_info_bundles() as $entity => $bundles) {
    $form['bundle'][] = array('#markup' => t('@entity entity', array('@entity' => $entity)), '#id' => $entity);
    foreach ($bundles as $bundle => $value) {
      $options[$bundle] = '';
      $form['bundle'][$bundle] = array(
        '#type' => 'item',
        '#markup' => '-- ' . $value['label'],
      );

    }
  }

  // Add the fields that exist in the bundle.
  foreach (field_info_fields() as $field => $value) {
    if (!empty($value['bundles'])) {
      foreach ($value['bundles'] as $entity) {
        foreach ($entity as $bundle) {
          $status[$field][$bundle] = $bundle;
        }
      }
    }
  }

  // Have to build checkboxes here after checkbox arrays are built
  foreach ($og_fields as $field => $field_value) {
    if (in_array($group_type, $field_value['type'])) {
      $form['checkboxes'][$field] = array('#type' => 'checkboxes', '#options' => $options, '#default_value' => !empty($status[$field]) ? $status[$field] : array());
      $form['modules'][$field] = array('#markup' =>  $field_value['instance']['label'] . ' (' . $field_value['module'] . ')', '#tree' => TRUE);
    }
  }

  $form['#theme'] = array('og_ui_user_admin_bundles');

  return $form;
}

function og_ui_admin_settings_submit($form, &$form_state) {
  $messages = array();
  $og_fields = og_get_fields();
  $bundle_info = array();
  foreach (field_info_bundles() as $entity => $bundles) {
    foreach ($bundles as $key => $value) {
        // Get a two dimension array with the bundle as key and the entity
        // as the value.
      $bundle_info[$key] = $entity;
    }
  }

  foreach ($og_fields as $field_name => $field_value) {
    if (!empty($form_state['values'][$field_name]) && $bundles = array_filter($form_state['values'][$field_name])) {
      foreach ($bundles as $key => $bundle) {
        // Add the field if it doesn't already exist.
        if (!field_info_instance($bundle_info[$bundle], $field_name, $bundle)) {
          call_user_func('og_create_field', $field_name, $bundle_info[$bundle], $bundle);
          $messages[] = t('Added field %field to %bundle.', array('%field' => $og_fields[$field_name]['instance']['label'], '%bundle' => $bundle));
        }
      }
    }
  }

  if (!empty($messages)) {
    foreach ($messages as $message) {
      drupal_set_message($message);
    }
  }
}

/**
 * Theme the administer bundles page.
 *
 * @ingroup themeable
 */
function theme_og_ui_user_admin_bundles($variables) {
  $form = $variables['form'];

  $modules = array_keys(element_children($form['modules']));

  foreach (element_children($form['bundle']) as $key) {
    $row = array();
    // Module name.
    if (is_numeric($key)) {
      $row[] = array('data' => drupal_render($form['bundle'][$key]), 'class' => array('module'), 'id' => 'module-' . $form['bundle'][$key]['#id'], 'colspan' => count($modules) + 1);
    }
    else {
      // bundle row.
      $row[] = array(
        'data' => drupal_render($form['bundle'][$key]),
        'class' => array('bundle'),
      );
      foreach (element_children($form['checkboxes']) as $module) {
        $row[] = array('data' => drupal_render($form['checkboxes'][$module][$key]), 'class' => array('checkbox'), 'title' => t('@bundle bundle', array('@bundle' => $key)));
      }
    }
    $rows[] = $row;
  }
  $header[] = (t('bundle'));
  foreach (element_children($form['modules']) as $module) {
    $header[] = array('data' => drupal_render($form['modules'][$module]), 'class' => array('checkbox'));
  }
  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'bundles')));
  $output .= drupal_render_children($form);
  return $output;
}