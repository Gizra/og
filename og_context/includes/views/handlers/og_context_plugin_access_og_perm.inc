<?php

/**
 * @file
 * Contains views access plugin for OG permissions
 */

/**
 * Allow views to allow access to only users with a specified permission in the
 * current group.
 */
class og_context_plugin_access_og_perm extends views_plugin_access {
  /**
   * Determine if the current user has access or not.
   */
  function access($account) {
    // Attempt to get the group from the current context and determine if the
    // user has the appropriate permission within the group
    if ($group = og_context($this->options['group_type'])) {
      return og_user_access($group['group_type'], $group['gid'], $this->options['perm'], $account);
    }
    return FALSE;
  }

  /**
   * Determine the access callback and arguments.
   */
  function get_access_callback() {
    if ($group = og_context($this->options['group_type'])) {
      return array('og_user_access', array($group['group_type'], $group['gid'], $this->options['perm']));
    }
    return FALSE;
  }

  /**
   * Return a string to display as the clickable title for the
   * access control.
   */
  function summary_title() {
    return $this->options['perm'];
  }

  /**
   * Retrieve the options when this is a new access
   * control plugin
   */
  function option_definition() {
    $options = parent::option_definition();
    $options['perm'] = array('default' => 'edit group');
    $options['group_type'] = array('default' => 'node');

    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  function options_form(&$form, &$form_state) {
    $perms = array();
    // Get list of permissions
    $module_info = system_get_info('module');
    foreach (og_get_permissions() as $perm => $info) {
      $module_name = $module_info[$info['module']]['name'];
      $perms[$module_name][$perm] = strip_tags($info['title']);
    }

    $form['perm'] = array(
      '#type' => 'select',
      '#options' => $perms,
      '#title' => t('OG permission'),
      '#default_value' => $this->options['perm'],
      '#description' => t('Only users with the selected permission flag in a group retrieved from context will be able to access this display.')
    );
    $form['group_type'] = array(
      '#type' => 'select',
      '#options' => og_get_all_group_entity(),
      '#title' => t('Group type'),
      '#default_value' => $this->options['group_type'],
      '#description' => t('Determine what entity type that group should be of.')
    );
  }
}
