<?php
// $Id$

/**
 * @file
 * workflow_ng integration for og module
 */

/**
 * Implementation of hook_event_info()
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
 * Describes the arguments available for the og hook
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
 * handler to get user
 */
function og_workflow_ng_events_argument_og_user($uid, $gid) {
  return user_load(array('uid' => $uid)); 
}

/**
 * handler to get node
 */
function og_workflow_ng_events_argument_og_node($uid, $gid) {
  return node_load($gid);
}

/**
 * Implementation of hook_og()
 */
function og_og($op, $gid, $uid, $args){
  if (in_array($op, array('user insert', 'user delete'))) {   
    $op = str_replace(' ', '_', $op);    
    workflow_ng_invoke_event('og_'. $op, $uid, $gid);   
  }
  // Pending member was approved.
  elseif ($op = 'user update' && $args['is_active']) {
    workflow_ng_invoke_event('og_user_approved', $uid, $gid);
  }
}
