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
 * @} End of "addtogroup hooks".
 */