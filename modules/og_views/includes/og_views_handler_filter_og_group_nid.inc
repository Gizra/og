<?php
class og_views_handler_filter_og_group_nid extends views_handler_filter_numeric {
  function query() {
    $node = node_load((int)$this->value['value']);
    og_set_group_context($node);
    parent::query();
  }
}