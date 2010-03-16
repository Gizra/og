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
 * Add group permissions.
 */
function hook_og_permission() {
  return array(
    'subscribe' => array(
      'title' => t('Subscribe user to group'),
      'description' => t("Allow user to be a member of a group (approval required)."),
      'roles' => array(OG_ANONYMOUS_ROLE),
    ),
  );
}

/**
 * Set the default permissions to be assigned to members, by thier role.
 */
function hook_og_default_permissions() {
  return array(
    'foo' => array(OG_AUTHENTICATED_ROLE),
  );
}

/**
 * Set a default role that will be used as a global role.
 */
function hook_og_default_roles() {
  return array('super admin');
}

function hook_og_users_roles_grant($nid, $uid, $rid) {

}

function hook_og_users_roles_revoke($nid, $uid, $rid) {

}

/**
 * Define the table, ID and label columns of a fieldable entity.
 *
 * This is used so groups can appear in the OG audience field with their
 * sanitized name.
 */
function hook_og_entity_get_info() {
  return array(
    'my_entity' => array(
      'table' => 'foo',
      'id' => 'bar',
      'label' => 'baz',
    ),
  );
}

/**
 * Alter get entity label definitions.
 *
 */
function hook_og_entity_get_info_alter(&$data) {
  if (!empty($data['my_entity'])) {
    $data['my_entity']['table'] = 'new_foo';
  }
}

/**
 * @} End of "addtogroup hooks".
 */