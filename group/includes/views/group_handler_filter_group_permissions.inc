<?php
// $Id:$
/**
 * @file
 * Contains the group filter for group permissions.
 */

/**
 * Filter group permissions
 */
class group_handler_filter_group_permissions extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Group permissions');
      $options = array();
      $this->value_options = $options;

    }
  }

}
