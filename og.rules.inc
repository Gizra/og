<?php
// $Id$

/**
 * @file
 * Rules module functionality for the Organic groups module.
 */


/**
 * Implementation of hook_rules_action_info().
 */
function og_rules_action_info() {
  return array(
    'og_rules_action_subscribe_user' => array(
      'label' => t('Subscribe user to group'),
      'arguments' => array(
        'user' => array(
          'type' => 'user',
          'label' => t('User who will be subscribed'),
        ),
        'group' => array(
          'type' => 'node',
          'label' => t('Group that user will be subscribed to'),
        ),
        'state' => array(
          'type' => 'string',
          'label' => t('The subscription state'),
        ),
        'replace' => array(
          'type' => 'boolean',
          'label' => t('Replace <em>all</em> the user subscription with this one'),
        ),
      ),
      'module' => 'Organic groups',
    ),
    'og_rules_action_remove_user' => array(
      'label' => t('Unsubscribe user from group'),
      'arguments' => array(
        'user' => array('type' => 'user',
        'label' => t('User who will be unsubscribed'),
        ),
        'group' => array(
          'type' => 'node',
          'label' => t('Group that user will be unsubscribed from'),
        ),
      ),
      'module' => 'Organic groups',
    ),
    'og_rules_action_add_group_node' => array(
      'label' => t('Add group node settings to content'),
      'arguments' => array(
        'node' => array(
          'type' => 'node',
          'label' => t('Content that will become a group node'),
        ),
      ),
      'help' => t("When creating a group node organic groups module requires some group settings. This action should be used after 'Add new content' action, that adds a <a href=\"@group-node-type\">group node type</a> content, and will result with a new group node.", array('@group-node-type' => url('admin/og/og'))),
      'module' => 'Organic groups',
    ),
  );
}

/**
 * Action: Subscribe user to group.
 */
function og_rules_action_subscribe_user($user, $node, $state, $replace, $settings) {
  $groups = array('gid' => $node->nid, 'state' => $state);
  og_set_association('user', $user, $groups, $replace);
}

/**
 * Action: Unsubscribe user from group.
 */
function og_rules_action_remove_user($user, $node, $settings) {
  og_delete_association('user', $user, array($node->nid));
}

/**
 * Action:Add group node settings to content.
 */
function og_rules_action_add_group_node($node, $settings) {
  if (og_is_group_type($node->type)) {
    // Add og keys to the node.
    foreach ($settings['og_fieldset']['og_settings'] as $key => $value){
      $node->$key = $value;
    }
    return array('node' => $node);
  }
}

/**
 * Action:Add group node settings to content form.
 */
function og_rules_action_add_group_node_form($settings, &$form) {
  $node = !empty($settings['og_fieldset']['og_settings']) ? $settings['og_fieldset']['og_settings'] : array();
  $og_form = og_group_form($node, array());
  $form['settings']['og_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Organic groups form settings'),
  );
  $form['settings']['og_fieldset']['og_settings'] = $og_form;
}

/**
 * Implementation of hook_rules_condition_info().
 */
function og_rules_condition_info() {
  return array(
    'og_rules_condition_user_in_group' => array(
      'label' => t('User is group member'),
      'arguments' => array(
        'user' => array(
          'type' => 'user',
          'label' => t('User'),
        ),
        'group' => array(
          'type' => 'node',
          'label' => t('Group'),
        ),
      ),
      'help' => t('Evaluates to TRUE if the user is an approved member of the group. If the user is a pending member this condition will return FALSE.'),
      'module' => 'Organic groups',
    ),
    'og_rules_condition_content_is_group' => array(
      'label' => t('Content is a group'),
      'arguments' => array(
        'group' => array(
          'type' => 'node',
          'label' => t('Group'),
        ),
      ),
      'help' => t('Evaluates to TRUE if the content is a group.'),
      'module' => 'Organic groups',
    ),
    'og_rules_condition_content_is_group_post' => array(
      'label' => t('Content is a group content'),
      'arguments' => array(
        'group' => array(
          'type' => 'node',
          'label' => t('group content'),
        ),
      ),
      'help' => t('Evaluates to TRUE if the content is a group content.'),
      'module' => 'Organic groups',
    ),
  );
}

/**
 * Condition: User is a group member.
 */
function og_rules_condition_user_in_group($user, $node, $states, $settings) {
  return in_array($node->nid, $groups, og_get_object_groups('user', $user, $states));
}

/**
 * Condition: Content is a group node.
 */
function og_rules_condition_content_is_group($node, $settings) {
  return og_is_group_type($node->type);
}

/**
 * Condition: Content is a group content.
 */
function og_rules_condition_content_is_group_post($node, $settings) {
  return og_is_group_post_type($node->type);
}