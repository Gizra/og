<?php
// $Id:$
/**
 * Filter by group state.
 */
class group_handler_filter_group_state extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Group state');
      $this->value_options = group_group_states();
    }
  }
}
