<?php


/**
 * @file
 * Plugin to provide access control based on user roles in a group.
 */

$plugin = array(
  'title' => t('OG: user role in group'),
  'description' => t('Control access by group role. This applies only to node type groups.'),
  'callback' => 'og_role_ctools_access_check',
  'default' => array(
    'og_rids' => array(),
  ),
  'settings form' => 'og_role_ctools_access_settings',
  'settings form submit' => 'og_role_ctools_access_settings_submit',
  'summary' => 'og_role_ctools_access_summary',
  'required context' => array(
    new ctools_context_required(t('User'), 'user'),
    new ctools_context_optional(t('Node'), 'node'),
  ),
);

/**
 * Settings form for the 'by role' access plugin
 */
function og_role_ctools_access_settings($form, &$form_state, $conf) {
  $form['settings']['og_rids'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Group roles'),
    '#default_value' => $conf['og_rids'],
    '#options' => og_get_all_roles(FALSE),
    '#description' => t('Only the checked roles will be granted access.'),
    '#required' => TRUE,
  );
  return $form;
}

/**
 * Compress the roles allowed to the minimum.
 */
function og_role_ctools_access_settings_submit($form, &$form_state) {
  $form_state['values']['settings']['og_rids'] = array_keys(array_filter($form_state['values']['settings']['og_rids']));
}

/**
 * Check for access.
 */
function og_role_ctools_access_check($conf, $context) {
  list($user_context, $node_context) = $context;
  if (empty($user_context->data)) {
    return FALSE;
  }
  $account = $user_context->data;

  if (!empty($node_context->data)) {
    $node = $node_context->data;
    $user_og_roles = og_get_user_roles('node', $node->nid, $account->uid, FALSE);
    return array_intersect($conf['og_rids'], array_keys($user_og_roles));
  }
  else {
    $user_groups = og_get_groups_by_user($account);
    if (empty($user_groups)) {
      return FALSE;
    }
    else {
      $user_og_roles = array();
      foreach($user_groups as $group_type => $groups) {
        foreach ($groups as $group_id) {
          $user_og_roles += og_get_user_roles($group_type, $group_id, $account->uid, FALSE);
        }
      }
      return array_intersect($conf['og_rids'], array_keys($user_og_roles));
    }
  }
}

/**
 * Provide a summary description based upon the checked role.
 */
function og_role_ctools_access_summary($conf, $context) {
  list($user_context, $node_context) = $context;
  if (!isset($conf['og_rids'])) {
    $conf['og_rids'] = array();
  }
  $roles = og_get_all_roles(FALSE);
  $names = array();
  foreach (array_filter($conf['og_rids']) as $rid) {
    $names[] = check_plain($roles[$rid]);
  }

  $summary_args = array(
    '@identifier' => $user_context->identifier,
  );

  if (empty($names)) {
    $summary_string = '@identifier has "any role"';
  }
  else {
    $summary_string = '@identifier has "@role"';
    $summary_args['@role'] = implode(', ', $names);
  }

  if (!empty($node_context->data)) {
    $summary_string .= ' in "@group" group';
    $summary_args['@group'] = $node_context->identifier;
  }

  return t($summary_string, $summary_args);
}
