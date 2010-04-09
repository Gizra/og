<?php
class og_handler_field_group_member_count extends views_handler_field_numeric {
  function query() {
    // TODO: Depends on the return query -- http://drupal.org/node/735400
    $query = og_get_group_users(1, array(), array('count' => TRUE, 'return query' => TRUE));
    $args = $query->getArguments();

    // Get the complied query.
    $sql = $query->__toString();

    // Replce placeholders with their value.
    foreach ($args as $key => $value) {
      $sql = str_replace($key, $value, $sql);
    }

    // TODO: Why do we need to wrap sql with brackets ()?
    $this->field_alias = $this->query->add_field(NULL, '('. $sql .')', 'member_count');
  }

  function render($values) {
    return $values->member_count;
  }
}