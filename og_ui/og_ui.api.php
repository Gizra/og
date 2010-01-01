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

function hook_og_ui_get_group_admin() {
  $items = array();
  $items['add_people'] = array(
    'title' => t('Add people'),
    'description' => t('Add group members.'),
    // The final URL will be "og/$obj_type/$oid/admin/people/add-user".
    'href' => 'admin/people/add-user',
  );

  return $items;
}

function hook_og_ui_get_group_admin_alter(&$data) {

}

/**
 * @} End of "addtogroup hooks".
 */