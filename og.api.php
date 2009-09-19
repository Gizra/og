<?php
// $Id$

/**
 * @file
 * Hooks provided by the Organic groups module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter a group that is being fetched.
 * 
 * @param $group
 *   An object with the following keys:
 *   - nid: 
 *       The node ID of the group.
 *   - data:
 *       Optional; An array of data related to the association. The data is per
 *       per group, and it's up to an implementing module to act on the data.
 */
function hook_og_get_group_alter($group) {
  // Set the theme according to the user name.
  global $user;
  if ($user->name == 'foo') {
    // An implementing module should act on this data and change the theme
    // accordingly.
    $group->data['theme'] = 'MY_THEME';
  }  
}

/**
 * Alter a group that is being saved.
 * 
 * @param $group
 *   An object with the following keys:
 *   - nid: 
 *       The node ID of the group.
 *   - data:
 *       Optional; An array of data related to the association. The data is per
 *       per group, and it's up to an implementing module to act on the data.
 */
function hook_og_set_group_alter($group) {

}

/**
 * Return the types of group or group posts available.
 * 
 * @return
 *   An array keyed with the type name and it's value is an array with the 
 *   following keys:
 *   - type: The type can be "group", "group_post" or "omitted".
 *   - description: Explanation about the type.
 */
function hook_og_types_info() {
  // Add a wiki style group post.
  return array(
    'wiki' => array(
      'type' => 'group post',
      'description' => t('Wiki group post (any group member may edit).'),
    )
  );
}

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
 * Alter the groups an object is associated with.
 * 
 * User subscription for example is passed through here, allows modules to 
 * change them. Also user unsubscription is using this function. To identify
 * the type of action, we get the $op argument.
 * 
 * @param $edit
 *   An array with the groups the object will be associated with, passed by 
 *   reference.
 * @param $account
 *   The user being subscribed.
  @param $op
 *   Optional; The operation that is being done (e.g. "subscribe user" or 
 *   "unsubscribe content").
 */
function hook_og_set_association_alter(&$gids, $account, $op = '') {
  if ($op == 'subscribe user') {
    // 	Subscribe the user to another group.
    $gids[] = 1;
  }  
}


/**
 * @} End of "addtogroup hooks".
 */