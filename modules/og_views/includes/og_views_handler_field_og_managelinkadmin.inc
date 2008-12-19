<?php
/**
 * Field handler to add/remove an admin.
 *
 * @ingroup views_field_handlers
 */
class og_views_handler_field_og_managelinkadmin extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'og_uid', 'field' => 'uid');
    $this->additional_fields['is_admin'] = array('table' => 'og_uid', 'field' => 'is_admin');
    $this->additional_fields['nid'] = array('table' => 'og_uid', 'field' => 'nid');
  }
  
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }
  
  function render($values) {
    $nid = $values->{$this->aliases['nid']};
    $uid = $values->{$this->aliases['uid']};
    $is_admin = $values->{$this->aliases['is_admin']};
    $node = node_load($nid);
    // Only show links to group admins. And don't show next to group manager.
    if ($uid != $node->uid) {
      if ($is_admin) {
        $item = menu_get_item("og/delete_admin/$nid/$uid");
        if ($item['access']) {
          return l(t('Admin: Remove'), "og/delete_admin/$nid/$uid", array('query' => drupal_get_destination()));
         }
      }
      else {
        $item = menu_get_item("og/create_admin/$nid/$uid");
        if ($item['access']) {
          return l(t('Admin: Create'), "og/create_admin/$nid/$uid", array('query' => drupal_get_destination()));
        }
      }
    }
  }
}