<?php
// $Id$

/**
 * @file
 * Hooks provided by the Group module.
 */

/**
 * @addtgrouproup hooks
 * @{
 */

/**
 * Add group permissions.
 */
function hook_group_permission() {
  return array(
    'subscribe' => array(
      'title' => t('Subscribe user to group'),
      'description' => t("Allow user to be a member of a group (approval required)."),
      'roles' => array(GROUP_ANONYMOUS_ROLE),
    ),
  );
}

/**
 * Define group context handlers.
 *
 * @return
 *   Array keyed with the context handler name and an array of properties:
 *   - callback: The callback function that should return a an array of group
 *     IDs.
 *   - menu: TRUE indicates that the handler will try to find a context by the
 *     current menu item. Defaults to TRUE.
 *   - menu path: If "menu" property is TRUE, this property is required.
 *     An array of path the handler should be invoked. For example,
 *     if the user is viewing a node, the menu system is "node/%", and all
 *     group context handlers with this matching path, will be invoked.
 *   - priority: Optional; Indicate if the context result of this handler should
 *     be treated as a priority. A use case can be for example, the "session"
 *     context handler that returns the group context that is stored in the
 *     $_SESSION. By giving it a priority, we make sure that even if viewing
 *     different pages, the user will see the same group context.
 *     @see group_context_handler_session().
 */
function hook_group_context_handlers() {
  $items = array();

  $items['foo'] = array(
    'callback' => 'foo_context_handler_bar',
    'menu path' => array('foo/%', 'foo/%/bar'),
  );

  return $items;
}

/**
 * Alter the group context handlers.
 */
function hook_group_context_handlers_alter(&$items) {
  // Add another menu path that should invoke this handler.
  $items['foo']['menu path'][] = 'foo/%/baz';
}


/**
 * Set a default role that will be used as a global role.
 *
 * A global role, is a role that is assigned by default to all new groups.
 */
function hook_group_default_roles() {
  return array('super admin');
}

/**
 * Alter the default roles.
 *
 * The anonymous and authenticated member roles are not alterable.
 *
 * @param $roles
 *   Array with the default roles name.
 */
function hook_group_default_roles_alter(&$roles) {
  // Remove a default role.
  unset($roles['super admin']);
}

/**
 * Set the default permissions to be assigned to members, by their role.
 *
 * Roles should be defined via hook_group_default_roles().
 *
 * @return
 *   Array keyed with the permission name and the roles it applied to as the
 *   value.
 */
function hook_group_default_permissions() {
  return array(
    'foo' => array(GROUP_AUTHENTICATED_ROLE),
  );
}

/**
 * Alter the default permissions.
 *
 * @param $perms
 *   Array keyed with the permission name and the roles it applied to as the
 *   value.
 */
function hook_group_default_permissions_alter(&$perms) {
  // Add the permission to 'super admin' role, that should be defined
  // via hook_group_default_roles().
  $perms['foo'][] = 'super admin';
}

/**
 * Allow modules to act upon new group role.
 *
 * @param $role
 *   The group role object.
 */
function hook_group_user_role_insert($role) {
}

/**
 * Allow modules to act upon existing group role update.
 *
 * @param $role
 *   The group role object.
 */
function hook_group_user_role_update($role) {

}

/**
 * Allow modules to act upon existing group role deletion.
 *
 * @param $role
 *   The deleted group role object. The object is actually a dummy, as the data
 *   is already deleted from the database. However, we pass the object to allow
 *   implementing modules to properly identify the deleted role.
 */
function hook_group_user_role_delete($role) {

}


function hook_group_users_roles_grant($gid, $uid, $rid) {

}

function hook_group_users_roles_revoke($gid, $uid, $rid) {

}

/**
 * @} End of "addtgrouproup hooks".
 */