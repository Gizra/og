<?php
// $Id$

/**
 * @file
 * Hooks provided by the Node module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the association and data of an object with a group.
 * 
 * @param $object
 *   An object with the following keys:
 *   - nid: 
 *       The node ID of the group.
 *   - content_id:
 *       The ID of the object being associated with the group.
 *   - type:
 *       The type of the object being associated with the group. Can be for 
 *       example "user" or "node".
 *   - state:
 *       Optional; An arbitrary string representing the state of the 
 *       association. For example "pending" or "approved". Note that it up to an
 *       implementing module to act upon those states.
 *   - data:
 *       Optional; An array of data related to the association. The data is per
 *       association, not per group, which means that implementing modules may
 *       override "per group" data by "per association" data.
 *       
 */
function hook_og_save_association_alter($object) {
  // Change that state of the association according to the object type.
  if ($object->type == 'user') {
    $object->state = 'pending';
  }
}

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
      'type' => 'group_post',
      'description' => t('Wiki group post (any group member may edit).'),
    )
  );
}


/**
 * @} End of "addtogroup hooks".
 */