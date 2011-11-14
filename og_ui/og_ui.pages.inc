<?php


/**
 * @file
 * Page callbacks for Organic groups module.
 */

/**
 * Subscribe the current user to a group.
 *
 * @param $node
 *   The group node entity.
 * @param $uid
 *   Optional; The user ID of the subscribing user. If empty the current user
 *   will be used.
 */
function og_ui_subscribe($entity_type, $etid, $membership_type = OG_MEMBERSHIP_TYPE_DEFAULT) {
  global $user;
  $group = og_get_group($entity_type, $etid);
  if (!$group) {
    // Not a valid group node.
    drupal_not_found();
    return;
  }

  if (!$user->uid) {
    // Anonymous user can't request membership.
    $dest = drupal_get_destination();
    if (variable_get('user_register', 1)) {
      drupal_set_message(t('In order to join any group, you must <a href="!login">login</a>. After you have successfully done so, you will need to request membership again.', array('!login' => url("user/login", array('query' => $dest)))));
    }
    else {
      drupal_set_message(t('In order to join any group, you must <a href="!login">login</a> or <a href="!register">register</a> a new account. After you have successfully done so, you will need to request membership again.', array('!register' => url("user/register", array('query' => $dest)), '!login' => url("user/login", array('query' => $dest)))));
    }
    drupal_goto('user');
  }

  $entity = $group->getEntity();

  $redirect = FALSE;
  $params = array(
    '@user' => format_username($user),
    // TODO: check user has permissions to see label (e.g. node title).
    '@group' => og_entity_label($entity_type, $entity),
  );

  if (og_is_member($group->gid, 'user', $user, array(OG_STATE_BLOCKED))) {
    // User is blocked, give them access denied.
    drupal_access_denied();
    return;
  }
  if (og_is_member($group->gid, 'user', $user, array(OG_STATE_PENDING))) {
    // User is pending, return them back.
    $message = $user->uid == $user->uid ? t('You already have a pending membership for the group @group.', $params) : t('@user already has a pending membership for the  the group @group.', $params);
    $redirect = TRUE;
  }

  if (og_is_member($group->gid, 'user', $user, array(OG_STATE_ACTIVE))) {
    // User is already a member, return them back.
    $message = $user->uid == $user->uid ? t('You are already a member of the group @group.', $params) : t('@user is already a member of the group @group.', $params);
    $redirect = TRUE;
  }

  if ($redirect) {
    drupal_set_message($message);
    _group_ui_redirect($entity_type, $entity);
  }

  // Check the membership type is valid.
  $type = og_membership_type_load($membership_type);
  if (!$type) {
    // Fallback to the default membership type.
    $membership_type = OG_MEMBERSHIP_TYPE_DEFAULT;
  }

  if (og_user_access($group->gid, 'subscribe', $user) || og_user_access($group->gid, 'subscribe without approval', $user)) {
    // Show the user a subscription confirmation.
    return drupal_get_form('og_ui_confirm_subscribe', $group, $user, $membership_type);
  }
}

/**
 * Confirm subscribe form.
 */
function og_ui_confirm_subscribe($form, &$form_state, $group, $account, $membership_type) {
  $values = array(
    'state' => og_user_access($group->gid, 'subscribe without approval') ? OG_STATE_ACTIVE : OG_STATE_PENDING,
    'type' => $membership_type,
  );

  // Add group membership form.
  $og_membership = og_membership_create($group->gid, 'user', $account->uid, $values);
  $form_state['og_membership'] = $og_membership;
  field_attach_form('og_membership', $og_membership, $form, $form_state);

  if (og_user_access($group->gid, 'subscribe without approval')) {
    // Hide the user request field.
    if (!empty($form[OG_MEMBERSHIP_REQUEST_FIELD])) {
      $form[OG_MEMBERSHIP_REQUEST_FIELD]['#access'] = FALSE;
    }
  }

  $label = og_label($group->gid);
  return confirm_form($form, t('Are you sure you want to join the group %title?', array('%title' => $label)), "$group->entity_type/$group->etid", ' ', t('Join'), t('Cancel'));
}

/**
 * Validate handler; Confirm OG membership.
 */
function og_ui_confirm_subscribe_validate($form, &$form_state) {
  // @see entity_form_field_validate().
  $og_membership = $form_state['og_membership'];
  field_attach_form_validate('og_membership', $og_membership, $form, $form_state);
}

/**
 * Submit handler; Confirm OG membership.
 */
function og_ui_confirm_subscribe_submit($form, &$form_state) {
  $og_membership = $form_state['og_membership'];
  field_attach_submit('og_membership', $og_membership, $form, $form_state);

  $wrapper = entity_metadata_wrapper('og_membership', $og_membership);
  $wrapper->save();

  // Redirect back to entity.
  $form_state['redirect'] = $wrapper->group->entity->url->value();
}


/**
 * Confirm OG unsubscription form.
 *
 * The unsubscribing user is always the acting user - like this we make sure
 * no malicious user will unsubscribe another user. Administrators can reject or
 * ban another group member from the "people" page.
 */
function og_ui_unsubscribe($entity_type, $etid) {
  if ($group = og_get_group($entity_type, $etid)) {
    global $user;
    $label = og_label($group->gid);
    // Check the user isn't the manager of the group.
    $manager = $group->user();
    if ($manager->uid != $user->uid) {
      $account = clone $user;
      if (og_is_member($group->gid, 'user', $account, array(OG_STATE_ACTIVE, OG_STATE_PENDING))) {
        // Show the user a subscription confirmation.
        return drupal_get_form('og_ui_confirm_unsubscribe', $group, $account);
      }
    }
    else {
      drupal_set_message(t('As the manager of %group, you can not leave the group.', array('%group' => $label)));
    }
    $entity= $group->getEntity();
    _group_ui_redirect($group->entity_type, $entity);
  }
  // Not a valid group.
  drupal_not_found();
}

/**
 * Helper function; Redirect back to entity, if access is allowed.
 *
 * @param $entity_type
 * @param $entity
 * @return unknown_type
 */
function _group_ui_redirect($entity_type, $entity, $account = NULL) {
  // Redirect back to entity, if access is allowed. Access is checked only
  // for nodes.
  $uri = array();

  if ($entity_type == 'node') {
    if (!node_access('view', $entity, $account)) {
      $uri = array(
        'path' => '<front>',
        'options' => array(),
      );
    }
  }
  if (!$uri) {
    $uri = entity_uri($entity_type, $entity);
  }
  drupal_goto($uri['path'], $uri['options']);
}

/**
 * Confirm unsubscribe form.
 */
function og_ui_confirm_unsubscribe($form, &$form_state, $group, $account) {
   $form['group'] = array('#type' => 'value', '#value' => $group);
   $form['account'] = array('#type' => 'value', '#value' => $account);

   $label = og_label($group->gid);
   return confirm_form($form, t('Are you sure you want to unsubscribe from the group %title?', array('%title' => $label)), "$group->entity_type/$group->etid", ' ', t('Remove'), t('Cancel'));
}

/**
 * Submit handler; Confirm OG unsubscription.
 */
function og_ui_confirm_unsubscribe_submit($form, &$form_state) {
  $group = $form_state['values']['group'];
  $account = $form_state['values']['account'];

  og_ungroup($group->gid, 'user', $account);

  // TODO: Determine where to go next - Group if accessible, or else site front
  // page.
  $form_state['redirect'] = "$group->entity_type/$group->etid";
}