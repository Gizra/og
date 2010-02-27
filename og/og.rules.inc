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
  $items = array();

  $items['og_set_association'] = array(
    'label' => t('Associate content to group'),
    'parameter' => array(
      'group' => array(
        'type' => 'og',
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
    'group' => t('Organic groups'),
  );

  return $items;
}

/**
 * Action: Subscribe user to group.
 */
function og_rules_set_association($group, $content, $state, $settings) {
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
    'og_rules_condition_content_is_group_content' => array(
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
  return in_array($node->nid, $groups, og_get_entity_groups('user', $user, $states));
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
function og_rules_condition_content_is_group_content($node, $settings) {
  return og_is_group_content_type($node->type);
}