<?php
class og_handler_field_group_member_count extends views_handler_field_numeric {
  function query() {
    //TODO: Get the query from field_attach_query().
    $sql = "SELECT COUNT(*) FROM field_data_og_audience t WHERE (og_groups.gid = t.og_audience_gid)";

    $this->field_alias = $this->query->add_field(NULL, "($sql)", 'member_count');
  }

  function render($values) {
    return $values->member_count;
  }
}