<?php
// $Id$

/**
 * @file
 * Rules module functionality for the Group module.
 */


/**
 * Implementation of hook_rules_action_info().
 */
function group_rules_action_info() {
  $items = array();

  $items['group_set_association'] = array(
    'label' => t('Associate content to group'),
    'parameter' => array(
      'group' => array(
        'type' => 'group',
        'label' => t('Group, to associate the content to'),
      ),
      'content' => array(
        'type' => 'string',
        'label' => t('Content, to associate to the group'),
      ),
      'state' => array(
        'type' => 'string',
        'label' => t('State'),
        'description' => t('The state of the association.'),
      ),
    ),
    'group' => t('Group'),
  );

  return $items;
}

/**
 * Action: Subscribe user to group.
 */
function group_rules_set_association($group, $content, $state, $settings) {
  $groups = array('gid' => $node->nid, 'state' => $state);
  group_set_association('user', $user, $groups, $replace);
}

/**
 * Action: Unsubscribe user from group.
 */
function group_rules_action_remove_user($user, $node, $settings) {
  group_delete_association('user', $user, array($node->nid));
}

/**
 * Action:Add group node settings to content.
 */
function group_rules_action_add_group_node($node, $settings) {
  if (group_is_group_type($node->type)) {
    // Add group keys to the node.
    foreach ($settings['group_fieldset']['group_settings'] as $key => $value){
      $node->$key = $value;
    }
    return array('node' => $node);
  }
}

/**
 * Action:Add group node settings to content form.
 */
function group_rules_action_add_group_node_form($settings, &$form) {
  $node = !empty($settings['group_fieldset']['group_settings']) ? $settings['group_fieldset']['group_settings'] : array();
  $group_form = group_group_form($node, array());
  $form['settings']['group_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => t('Group form settings'),
  );
  $form['settings']['group_fieldset']['group_settings'] = $group_form;
}

/**
 * Implementation of hook_rules_condition_info().
 */
function group_rules_condition_info() {
  return array(
    'group_rules_condition_user_in_group' => array(
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
      'module' => 'Group',
    ),
    'group_rules_condition_content_is_group' => array(
      'label' => t('Content is a group'),
      'arguments' => array(
        'group' => array(
          'type' => 'node',
          'label' => t('Group'),
        ),
      ),
      'help' => t('Evaluates to TRUE if the content is a group.'),
      'module' => 'Group',
    ),
    'group_rules_condition_content_is_group_content' => array(
      'label' => t('Content is a group content'),
      'arguments' => array(
        'group' => array(
          'type' => 'node',
          'label' => t('group content'),
        ),
      ),
      'help' => t('Evaluates to TRUE if the content is a group content.'),
      'module' => 'Group',
    ),
  );
}

/**
 * Condition: User is a group member.
 */
function group_rules_condition_user_in_group($user, $node, $states, $settings) {
  return in_array($node->nid, $groups, group_get_entity_groups('user', $user, $states));
}

/**
 * Condition: Content is a group node.
 */
function group_rules_condition_content_is_group($node, $settings) {
  return group_is_group_type($node->type);
}

/**
 * Condition: Content is a group content.
 */
function group_rules_condition_content_is_group_content($node, $settings) {
  return group_is_group_content_type($node->type);
}