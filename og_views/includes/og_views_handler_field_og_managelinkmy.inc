<?php
class og_views_handler_field_og_managelinkmy extends views_handler_field {
  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    global $user;
    return l(t('Edit membership'), "og/manage/$values->nid", array('query' => drupal_get_destination()));
  }
}