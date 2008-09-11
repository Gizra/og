<?php
class og_views_handler_field_og_member_count extends views_handler_field_numeric {
  function query() {
    $table = $this->query->ensure_table('og');
    $sql = og_list_users_sql(1, 0, NULL, TRUE);
    $sql = str_replace('%d', 'og.nid', $sql);
    $this->query->add_field('', "($sql)", 'member_count');
    $this->field_alias = 'member_count';
  }
  
  function render($values) {
    $nid = $values->nid;
    $txt = $values->member_count;
    if (og_is_group_member($nid)) {
      return og_is_picture() ? l($txt, "og/users/$nid/faces") : l($txt, "og/users/$nid");
    }
    else {
      return parent::render($values);
    }
  }
}