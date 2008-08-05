<?php
// $Id$

/**
 * @file
 * Rules integration for the og module.
 */

/**
 * Implementation of hook_rules_event_info().
 */
function og_rules_event_info() {
  return array(
    'og_user_insert' => array(
      'label' => t('User subscribes to group'),      
      'description' => t("A user has subscribed to a group and is approved. If the user isn't approved then this event won't be triggered."),
      'arguments' => og_rules_events_hook_og_arguments(),
      'module' => 'Organic groups',
    ),
    'og_user_approved' => array(
      'label' => t('User approved to group by admin'),      
      'description' => t('A pending member is approved by a group administrator.'),
      'arguments' => og_rules_events_hook_og_arguments(),
      'module' => 'Organic groups',
    ),
    'og_user_delete' => array(
      'label' => t('User unsubscribes from group'),
      'description' => t('A user has unsubscribed from a group.'),
      'arguments' => og_rules_events_hook_og_arguments(),
      'module' => 'Organic groups',
   ),
 );    
}

/**
 * Describes the arguments available for the og_hook().
 * 
 * We pass uid and gid to rules so that the argument handlers can 
 * load the full entities. As an affect uid and gid must be mentioned here too.
 */
function og_rules_events_hook_og_arguments() {
  return array(
    'uid' => NULL,
    'gid' => NULL,
    'account' => array(
      'type' => 'user', 
      'label' => t('User that subscribed to the group'), 
      'handler' => 'og_rules_events_argument_og_user',
    ),
    'group' => array(
      'type' => 'node', 
      'label' => t('Group'), 
      'handler' => 'og_rules_events_argument_og_node',
    ),
  ) + rules_events_global_user_argument();
}

/**
 * Handler to get the user.
 */
function og_rules_events_argument_og_user($uid, $gid) {
  return user_load(array('uid' => $uid)); 
}

/**
 * Handler to get the group node.
 */
function og_rules_events_argument_og_node($uid, $gid) {
  return node_load($gid);
}

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
      ),
      'description' => t('Subscribe a user to a group.'),
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
      'description' => t('Unsubscribe a user from a group.'),
      'module' => 'Organic groups',
    ),
  );
}

/**
 * Action: Subscribe user to group.
 */
function og_rules_action_subscribe_user($user, $node, $settings) {
  if (!og_is_group_member($node->nid, $user)) {
    og_save_subscription($node->nid, $user->uid, array('is_active' => (int)$settings['is_active']));
  }
}

/**
 * Action: Subscribe user to group form.
 *
 * @ingroup forms.
 */
function og_rules_action_subscribe_user_form($settings, &$form) {
  $form['settings']['is_active'] = array(
    '#type' => 'checkbox',
    '#title' => t('Subscription is approved'),
    '#description' => t('When enabled the user will automatically be approved. When disabled user will be a pending member.'),
    '#default_value' => $settings['is_active'],
  );
}


/**
 * Action: Unsubscribe user from group.
 */
function og_rules_action_remove_user($user, $node, $settings) {
  og_delete_subscription($node->nid, $user->uid);
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
      'description' => t('Evaluates to TRUE if the user is an approved member of the group. If the user is a pending member this condition will return FALSE.'),
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
      'description' => t('Evaluates to TRUE if the content is a group.'),
      'module' => 'Organic groups',
    ),    
  );
}

/**
 * Condition: User is group member.
 */
function og_rules_condition_user_in_group($user, $node, $settings) {
  return !empty($node->nid) && og_is_group_member($node->nid, $user);
}

/**
 * Condition: Content is a group.
 */
function og_rules_condition_content_is_group($node, $settings) {
  return og_is_group_type($node->type);
}
