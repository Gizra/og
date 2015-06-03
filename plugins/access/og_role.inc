<?php


/**
 * @file
 * Plugin to provide access control based on user roles in a group.
 */

$plugin = array(
  'title' => t('OG: user role in group'),
  'description' => t('Control access by group role.'),
  'callback' => 'og_role_ctools_access_check',
  'default' => array(
    'og_rids' => array(),
  ),
  'settings form' => 'og_role_ctools_access_settings',
  'settings form submit' => 'og_role_ctools_access_settings_submit',
  'summary' => 'og_role_ctools_access_summary',
  'required context' => array(
    new ctools_context_required(t('User'), 'user'),
    new ctools_context_required(t('Node'), 'node'),
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
    '#options' => og_get_all_roles(),
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
  if (empty($user_context->data) || empty($node_context->data)) {
    return FALSE;
  }
  $account = $user_context->data;
  $node = $node_context->data;

  $user_og_roles = og_get_user_roles('node', $node->nid, $account->uid, FALSE);
  return count(array_intersect($conf['og_rids'], array_keys($user_og_roles))) > 0;
}

/**
 * Provide a summary description based upon the checked role.
 */
function og_role_ctools_access_summary($conf, $context) {
  list($user_context, $node_context) = $context;
  if (!isset($conf['og_rids'])) {
    $conf['og_rids'] = array();
  }
  $roles = og_get_all_roles();
  $names = array();
  foreach (array_filter($conf['og_rids']) as $rid) {
    $names[] = check_plain($roles[$rid]);
  }
  if (empty($names)) {
    return t('@identifier has "any role in @group group"', array(
      '@identifier' => $user_context->identifier,
      '@group' => $node_context->identifier,
    ));
  }
  return t('@identifier has "@role in @group group"', array(
    '@identifier' => $user_context->identifier,
    '@role' => implode(', ', $names),
    '@group' => $node_context->identifier,
  ));
}
