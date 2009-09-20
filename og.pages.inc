<?php
// $Id: og.pages.inc,v 1.1.2.2 2009/06/10 01:07:27 weitzman Exp $

/**
 * @file
 * Page callbacks for Organic groups module.
 */

/**
 * Manage user subscription to a group.
 *
 * @param $node
 *   The group node object.
 * @param $uid
 *   Optional; The user ID of the subscribing user. If empty the current user
 *   will be used.
 */
function og_subscribe($node, $uid = NULL) {
  if (og_is_group_type($node->type)) {
    if (empty($uid)) {
      global $user;
      $account = $user;

	    if (!$account->uid) {
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
    
    //FIXME: Remove
    $account = user_load($account->uid);
    // Check user isn't already subscribed.
    if (in_array($node->nid, og_get_groups($account))) {
      drupal_set_message(t('@user is already a member the group @group.', array('@user' => $account->name, '@group' => $node->title)));
    	drupal_goto('node/'. $node->nid);
    }

    elseif (og_get_group_selective($node) >= OG_INVITE_ONLY || $node->status == 0) {
    	// Invite only group, or unpublished node.
      drupal_access_denied();
      exit();
    }
    else {
    	// Show the user a subscription confirmation.
      return drupal_get_form('og_confirm_subscribe', $node, $account);
    }
  }
  // Not a valid group node.
  drupal_not_found();

}

/**
 * Confirm OG membership form.
 */
function og_confirm_subscribe($form, &$form_state, $node, $account) {
  $form['gid'] = array('#type' => 'value', '#value' => $node->nid);
  $form['account'] = array('#type' => 'value', '#value' => $account);
  if (og_get_group_selective($node) == OG_MODERATED) {
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
  $request = !empty($form_state['values']['request']) ? $form_state['values']['request'] : '';
  $gid = $form_state['values']['gid'];
  
  $node = node_load($gid);
  
  $group = array(
    'value' => $gid,
    // Get the state of the group, and decide the user's state accordingly.
    'state' => og_get_group_selective($node) == OG_OPEN ? OG_STATE_ACTIVE : OG_STATE_PENDING,
  );

	og_subscribe_user(array($group), $request);
  $form_state['redirect'] = 'node/'. $form_state['values']['gid'];
}


/**
 * Confirm OG unsubscription form.
 */
function og_confirm_unsubscribe($form, &$form_state, $group_node, $account) {
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
  
  og_unsubscribe_user(array($form_state['values']['gid']), $form_state['values']['account']);
  
  // Determine where to go next - Group if accessible, or else site front page.
  $form_state['redirect'] = og_access('view', $group_node) ? 'node/' . $group_node->nid : '';
}

/**
 * Add users to group form.
 */
function og_add_users($form, &$form_state, $group_node) {
  $form['og_names'] = array(
    '#type' => 'textarea',
    '#title' => t('List of users'),
    '#rows' => 5,
    '#cols' => 70,
    // '#autocomplete_path' => 'user/autocomplete',
    '#description' => t('Add one or more usernames in order to associate users with this group. Multiple usernames should be separated by a comma.'),
    '#element_validate' => array('og_add_users_og_names_validate'),
  );
  $form['op'] = array('#type' => 'submit', '#value' => t('Add users'));
  $form['gid'] = array('#type' => 'value', '#value' => $group_node->nid);
  return $form;
}

/**
 * Validate handler; Add users to group.
 */
function og_add_users_og_names_validate($form, &$form_state) {
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

/**
 * Submit handler; Add users to group.
 */
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