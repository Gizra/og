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
        if (variable_get('user_register', 1)) {
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

    // Check user isn't already subscribed.
    if (in_array($node->nid, og_get_groups('user', $account))) {
      drupal_set_message(t('@user is already a member the group @group.', array('@user' => $account->name, '@group' => $node->title)));
      drupal_goto('node/'. $node->nid);
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
  if (!og_user_access($node->nid, 'subscribe without approval')) {
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
  $nid = $form_state['values']['gid'];

  $node = node_load($nid);

  $group = array(
    'value' => $nid,
    // Get the state of the group, and decide the user's state accordingly.
    'state' => og_user_access($nid, 'subscribe without approval') ? OG_STATE_ACTIVE : OG_STATE_PENDING,
  );

  og_subscribe_user(array($group), NULL, FALSE, $request);

  $form_state['redirect'] = node_access('view', 'node/'. $form_state['values']['gid']) ? 'node/'. $form_state['values']['gid'] : '';
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
  $group_node = $form_state['values']['group_node'];

  og_unsubscribe_user(array($group_node->nid), $form_state['values']['account']);

  // Determine where to go next - Group if accessible, or else site front page.
  $form_state['redirect'] = og_access('view', $group_node) ? 'node/' . $group_node->nid : '';
}

/**
 * Add users to group form.
 */
function og_add_users($form, &$form_state, $group_node) {
  $form['og_add_user'] = array(
    '#type' => 'textfield',
    '#title' => t('Add user'),
    '#autocomplete_path' => 'user/autocomplete',
    '#description' => t('Add a user to group %group.', array('%group' => $group_node->title)),
    '#element_validate' => array('og_add_users_og_names_validate'),
  );
  $form['op'] = array('#type' => 'submit', '#value' => t('Add users'));
  $form['gid'] = array('#type' => 'value', '#value' => $group_node->nid);
  return $form;
}

/**
 * Validate handler; Add users to group.
 */
function og_add_users_validate($form, &$form_state) {
  $account = user_load_by_name($form_state['values']['og_add_user']);
  if (!$account) {
    form_set_error('og_add_user', t('You have entered an invalid user name.'));
  }
}

/**
 * Submit handler; Add users to group.
 */
function og_add_users_submit($form, &$form_state) {
  $group = array(
    'value' => $form_state['values']['gid'],
    'state' => OG_STATE_ACTIVE,
  );

  og_subscribe_user(array($group));
}

/**
 * Allow user to approve another user's membership.
 *
 * @param $node
 *   The node object.
 * @param $account
 *   The user to be approved object.
 * @param $token
 *   A security token.
 * @return
 */
function og_approve($node, $account, $token) {
  if (og_is_group_type($node->type)) {
    if (!og_check_token($token, $node->nid)) {
      drupal_set_message(t('Bad token. You seem to have followed an invalid link.'), 'error');
      drupal_access_denied();
      return;
    }

    $user_groups = og_get_groups('user', $account);

    if (in_array($node->nid, $user_groups)) {
      drupal_set_message(t('!name was already approved to group %group.', array('!name' => theme('username', $account), '%group' => $node->title)), 'error');
      return '';
    }
    else {
      og_subscribe_user(array(array('value' => $node->nid, 'state' => OG_STATE_ACTIVE)), $account);
      og_invoke_event();

      drupal_goto("node/$node->nid");
    }
  }
  else {
    drupal_not_found();
    exit;
  }
}