<?php
// $Id$

/**
 * @file
 * Hooks provided by the Organic groups UI module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the users which will be notified about a subscription of another user.
 * 
 * @param $uids
 *   An array with the users ID, passed by reference.
 * @param $node
 *   The group node, the user has subscribed to.
 * @param $group
 *   The group object.
 * @param $account
 *   The subscribing user object.
 * @param $request
 *   Optional; The request text the subscribing user has entered.
 */
function hook_og_ui_user_request(&$uids, $node, $group, $account, $request) {
  // Add user ID 1 to the list of notified users.
  $uids[] = 1;
}

/**
 * Define selective types of groups. 
 */
function og_ui_og_group_selective_info() {
  return array(
    'private' => t('Private'),
  ); 
}

/**
 * @} End of "addtogroup hooks".
 */
