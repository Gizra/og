<?php
// $Id: og.pages.inc,v 1.1.2.2 2009/06/10 01:07:27 weitzman Exp $

/**
 * @file
 * Page callbacks for Organic groups UI.
 */

/**
 * Manage user subscription to a group.
 * 
 * @param $node
 *   The group node object.
 * @param $uid
 *   The user ID of the subscribing user.
 * @return unknown_type
 */
function og_subscribe($node, $uid = NULL) {
  if (og_is_group_type($node->type)) {
    global $user;
    if (empty($uid)) {
      if ($user->uid) {
        $account = $user;
      }
      else {
        $dest = drupal_get_destination();
        if (variable_get('user_register', 1) == 0) {
          drupal_set_message(t('In order to join this group, you must <a href="!login">login</a>. After you have successfully done so, you will need to request membership again.', array('!login' => url("user/login", array('query' => $dest)))));
        }
        else {
          drupal_set_message(t('In order to join this group, you must <a href="!login">login</a> or <a href="!register">register</a> a new account. After you have successfully done so, you will need to request membership again.', array('!register' => url("user/register", array('query' => $dest)), '!login' => url("user/login", array('query' => $dest)))));
        }
        drupal_goto('user');
      }
    }
    else {
      $account = user_load($uid);
    }
    
    if (og_get_group_selective($node) >= OG_INVITE_ONLY || $node->status == 0) {
      drupal_access_denied();
      exit();
    }
    
    // Only admins can add another member.
    if ($account->uid != $user->uid && !og_is_group_admin($node)) {
      drupal_access_denied();
      exit();
    }
    // User is already a member of this group, redirect to group homepage.
    else if (isset($account->og_groups[$node->nid])) {
      drupal_set_message(t('@user is already a member the group @group.', array('@user' => $account->name, '@group' => $node->title)));
      drupal_goto('node/'. $node->nid);
    }
    else {
      return drupal_get_form('og_confirm_subscribe', $node->nid, $node, $account);
    }
  }
  // Not a valid group node.
  drupal_not_found();
  
}

/**
 * Confirm OG membership form.
 */
function og_confirm_subscribe($form_state, $gid, $node, $account) {
  $form['gid'] = array('#type' => 'value', '#value' => $gid);
  $form['account'] = array('#type' => 'value', '#value' => $account);
  if ($node->og_selective == OG_MODERATED) {
    $form['request'] = array(
      '#type' => 'textarea', 
      '#title' => t('Additional details'), 
      '#description' => t('Add any detail which will help an administrator decide whether to approve or deny your membership request.')
    );
  }
 return confirm_form($form, t('Are you sure you want to join the group %title?', array('%title' => $node->title)), 'node/'. $node->nid, ' ', t('Join'), t('Cancel'));
}

/**
 * Submit handler; Confirm OG membership.
 */
function og_confirm_subscribe_submit($form, &$form_state) {
  $form_state['values']['request'] = !empty($form_state['values']['request']) ? $form_state['values']['request'] : '';
  
	$return = og_subscribe_user(array($form_state['values']['gid']), $form_state['values']['account'], $form_state['values']['request']);
  $form_state['redirect'] = 'node/'. $form_state['values']['gid'];
} 


/**
 * Confirm OG unsubscription form.
 */
function og_confirm_unsubscribe($form_state, $group_node, $account) {
  $form['group_node'] = array('#type' => 'value', '#value' => $group_node);
  $form['account'] = array('#type' => 'value', '#value' => $account);
  return confirm_form($form, t('Are you sure you want to remove !name from the group %title?', array('!name' => theme('username', $account), '%title' => $group_node->title)), 'og/users/'. $group_node->nid, ' ', t('Remove'), t('Cancel'));
}

/**
 * Submit handler; Confirm OG unsubscription.
 */
function og_confirm_unsubscribe_submit($form, &$form_state) {
  global $user;
  $group_node = $form_state['values']['group_node'];
  $account = $form_state['values']['account'];
  og_delete_subscription($group_node->nid, $account->uid);
  // If needed, reload user object to reflect unsubscribed group.
  if ($user->uid == $account->uid) {
    og_get_subscriptions($account->uid, 1, TRUE); // Clear cache.
    $user = user_load(array('uid' => $user->uid));
  }
  drupal_set_message(t('%user removed from %group.', array('%user' => $account->name, '%group' => $group_node->title)));
  
  // Determine where to go next. GHP if accessible, or else site front page.
  $form_state['redirect'] = node_access('view', $group_node) ? "node/". $group_node->nid : '';
}

function og_add_users($form_state, $group_node) {
  $form['og_names'] = array(
    '#type' => 'textarea',
    '#title' => t('List of users'),
    '#rows' => 5,
    '#cols' => 70,
    // No autocomplete b/c user_autocomplete can't handle commas like taxonomy. pls improve core.
    // '#autocomplete_path' => 'user/autocomplete',
    '#description' => t('Add one or more usernames in order to associate users with this group. Multiple usernames should be separated by a comma.'),
    '#element_validate' => array('og_add_users_og_names_validate'),
  );
  $form['op'] = array('#type' => 'submit', '#value' => t('Add users'));
  $form['gid'] = array('#type' => 'value', '#value' => $group_node->nid);
  return $form;
}

// An #element_validate handler
function og_add_users_og_names_validate($form, $form_state) {
  $names = explode(',', $form_state['values']['og_names']);
  foreach ($names as $name) {
    $account = user_load(array('name' => trim($name)));
    if (isset($account->uid)) {
      $accounts[] = $account;
      $uids[] = $account->uid;
    }
    else {
      $bad[] = check_plain($name);
      $err = TRUE;
    }
  }
  if (isset($err)) {
    form_set_error('og_names', format_plural(count($bad), 'Unrecognized name: %bad.', 'Unrecognized names: %bad.', array('%bad' => implode(', ', $bad))));
  }
}

function og_add_users_submit($form, &$form_state) {
  // Safest option is to do a select, filter existing members, then insert.
  $names = explode(',', $form_state['values']['og_names']);
  foreach ($names as $name) {
    $account = user_load(array('name' => trim($name)));
    if ($account->uid) {
      $accounts[] = $account;
    }
  }
  foreach ($accounts as $account) {
    og_save_subscription($form_state['values']['gid'], $account->uid, array('is_active' => 1));
  }
  drupal_set_message(format_plural(count($accounts), '1 user added to the group.', '@count users added to the group.'));
}