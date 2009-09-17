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
function hook_og_user_request(&$uids, $node, $group, $account, $request) {
  // Add user ID 1 to the list of notified users.
  $uids[] = 1;
}

/**
 * Define selective types of groups. 
 */
function hook_og_selective_info() {
  return array(
    'private' => t('Private'),
  ); 
}

/**
 * Insert state and data, that will be saved with the group post.
 * 
 * @param $alter
 *   Array keyed by "state" and "data" passed by reference.
 *   The data passed by reference.
 * @param $obj_type
 * @param $object
 * @param $field
 * @param $instance
 * @param $langcode
 * @param $items
 */
function hook_og_field_insert(&$alter, $obj_type, $object, $field, $instance, $langcode, $items) {
  // Add timestamp for the subscription.
  // It's up to the implementing module to act on the data.
  $alter['data']['timestamp'] = time();
}


/**
 * Update state and data, that will be saved with the group post.
 * 
 * @param $alter
 *   Array keyed by "state" and "data" passed by reference.
 *   The data passed by reference.
 * @param $obj_type
 * @param $object
 * @param $field
 * @param $instance
 * @param $langcode
 * @param $items
 */
function hook_og_field_update (&$alter, $obj_type, $object, $field, $instance, $langcode, $items) {
	// Reject a group post when it's updated.
	// It's up to the implementing module to act on the data.
	$alter['state'] = 'updated, approve urgently';
}

/**
 * @} End of "addtogroup hooks".
 */
