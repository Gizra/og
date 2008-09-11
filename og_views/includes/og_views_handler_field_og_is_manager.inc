<?php
/**
 * Field handler to show if user is manager or not of the displayed group node.
 *
 * @ingroup views_field_handlers
 */
class og_views_handler_field_og_is_manager extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'og_uid', 'field' => 'uid');
    // TODOL: Sometimes node table is not recognized (i.e. when listing users). 
    $this->additional_fields['node_uid'] = array('table' => 'node', 'field' => 'uid');
  }
  
  function query() {
    $this->ensure_my_table();
    $this->query->ensure_table('node');
    $this->add_additional_fields();
  }
  
  function render($values) {
    $uid = $values->{$this->aliases['uid']};
    $node_uid = $values->{$this->aliases['node_uid']};
    if ($uid == $node_uid) {
      return '<em>'. t('Manager'). '</em>'; 
    }
  }
}