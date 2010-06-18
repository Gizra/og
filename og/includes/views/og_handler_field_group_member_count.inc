<?php
class og_handler_field_group_member_count extends views_handler_field_numeric {
  function init(&$view, $options) {
    parent::init($view, $options);
    $this->additional_fields['gid'] = array('table' => 'group_groups', 'field' => 'gid');
  }

  function query() {
    //TODO: Get the query from field_attach_query().
    $table = $this->relationship;
    $sql = "SELECT COUNT(*) FROM {field_data_group_audience} t WHERE ($table.gid = t.group_audience_gid)";

    $this->field_alias = $this->query->add_field(NULL, "($sql)", 'member_count');
  }
}