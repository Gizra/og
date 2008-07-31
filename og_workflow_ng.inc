<?php
// $Id$

/**
 * @file
 * workflow_ng integration for og module.
 */

/**
 * Implementation of hook_event_info().
 */
function og_event_info() {
  return array(
    'og_user_insert' => array(
      '#label' => t('User joins group'),
      '#module' => t('OG'),
      '#arguments' => og_workflow_ng_events_hook_og_arguments(),
    ),
    'og_user_approved' => array(
      '#label' => t('User approved to group by admin'),
      '#module' => t('OG'),
      '#description' => t('A pending member is approved by a group administrator.'),
      '#arguments' => og_workflow_ng_events_hook_og_arguments(),
    ),
    'og_user_delete' => array(
      '#label' => t('User leaves group'),
      '#module' => t('OG'),
      '#arguments' => og_workflow_ng_events_hook_og_arguments(),
   ),
 );    
}

/**
 * Describes the arguments available for the og hook.
 * 
 * We pass uid and gid to workflow-ng so that the argument handlers can load the full entities.
 * As an affect uid and gid must be mentioned here too.
 */
function og_workflow_ng_events_hook_og_arguments() {
  return array(
    'uid' => NULL,
    'gid' => NULL,
    'account' => array('#entity' => 'user', '#label' => t('User, who joins group'), '#handler' => 'og_workflow_ng_events_argument_og_user'),
    'group' => array('#entity' => 'node', '#label' => t('Group'), '#handler' => 'og_workflow_ng_events_argument_og_node'),
  ) + workflow_ng_events_global_user_argument();
}

/**
 * handler to get user.
 */
function og_workflow_ng_events_argument_og_user($uid, $gid) {
  return user_load(array('uid' => $uid)); 
}

/**
 * handler to get node.
 */
function og_workflow_ng_events_argument_og_node($uid, $gid) {
  return node_load($gid);
}

/**
 * Implementation of hook_og().
 */
function og_og($op, $gid, $uid, $args) {
  if (in_array($op, array('user insert', 'user delete'))) {   
    $op = str_replace(' ', '_', $op);    
    workflow_ng_invoke_event('og_'. $op, $uid, $gid);   
  }
  // Pending member was approved.
  elseif ($op = 'user update' && $args['is_active']) {
    workflow_ng_invoke_event('og_user_approved', $uid, $gid);
  }
}


/**
 * Implementation of hook_action_info().
 */
function og_action_info() {
  return array(
    'og_workflow_ng_action_subscribe_user' => array(
      '#label' => t('Subscribe user to group'),
      '#arguments' => array(
        'user' => array('#entity' => 'user', '#label' => t('User who will be subscribed')),
        'group' => array('#entity' => 'node', '#label' => t('Group that user will be subscribed to')),
      ),
      '#description' => t('Subscribe a user to a group.'),
      '#module' => 'OG',
    ),  
    'og_workflow_ng_action_remove_user' => array(
      '#label' => t('Unsubscribe user from group'),
      '#arguments' => array(
        'user' => array('#entity' => 'user', '#label' => t('User who will be unsubscribed')),
        'group' => array('#entity' => 'node', '#label' => t('Group that user will be unsubscribed from')),
      ),
      '#description' => t('Unsubscribe a user from a group.'),
      '#module' => 'OG',
    ),
  );
}

/**
 * Action: Subscribe user to group.
 */
function og_workflow_ng_action_subscribe_user($user, $node, $settings) {
  if (!og_is_group_member($node->nid, $user)) {
    og_save_subscription($node->nid, $user->uid, array('is_active' => (int)$settings['is_active']));
  }
}

/**
 * Subscribe user to group form.
 *
 * @ingroup forms.
 * @see og_workflow_ng_action_subscribe_user_submit.
 */
function og_workflow_ng_action_subscribe_user_form($settings = array(), $argument_info) {
  $form = array();
  $form['is_active'] = array(
    '#type' => 'checkbox',
    '#title' => t('Subscription is approved'),
    '#description' => t('When enabled, the user will automatically be approved. When disabled user will be a pending member.'),
    '#default_value' => $settings['is_active'],
  );
  return $form;
}

function og_workflow_ng_action_subscribe_user_submit($form_id, $form_values) {
  $settings =  array('is_active' => $form_values['is_active']);
  return $settings;
}

/**
 * Action: Unsubscribe user from group.
 */
function og_workflow_ng_action_remove_user($user, $node, $settings) {
  og_delete_subscription($node->nid, $user->uid);
}

/**
 * Implementation of hook_condition_info().
 */
function og_condition_info() {
  return array(
    'og_workflow_ng_condition_user_in_group' => array(
    '#label' => t('User is group member'),
    '#arguments' => array(
      'user' => array('#entity' => 'user', '#label' => t('User')),
      'group' => array('#entity' => 'node', '#label' => t('Group')),
      ),
    '#description' => t('Evaluates to TRUE if the user is an approved member of the group. If the user is a pending member, this condition will return FALSE.'),
    '#module' => 'OG',
    ),
    'og_workflow_ng_condition_content_is_group' => array(
    '#label' => t('Content is a group'),
    '#arguments' => array(
      'group' => array('#entity' => 'node', '#label' => t('Group')),
      ),
    '#description' => t('Evaluates to TRUE if the content is a group.'),
    '#module' => 'OG',
    ),    
  );
  return $info;

}

/**
 * Condition: User is group member.
 */
function og_workflow_ng_condition_user_in_group($user, $node, $settings) {
  return og_is_group_member($node->nid, $user);
}

/**
 * Condition: Content is a group.
 */
function og_workflow_ng_condition_content_is_group($node, $settings) {
  return og_is_group_type($node->type);
}
