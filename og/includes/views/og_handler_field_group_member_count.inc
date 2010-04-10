<?php
class og_handler_field_group_member_count extends views_handler_field_numeric {
  function init(&$view, $options) {
    parent::init($view, $options);
    $this->additional_fields['gid'] = array('table' => 'og_groups', 'field' => 'gid');
  }

  function query() {
    //TODO: Get the query from field_attach_query().
    $table = $this->relationship;
    $sql = "SELECT COUNT(*) FROM {field_data_og_audience} t WHERE ($table.gid = t.og_audience_gid)";

    $this->field_alias = $this->query->add_field(NULL, "($sql)", 'member_count');
  }
}