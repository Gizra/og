<?php
/**
 * Field handler to show if user is admin or not.
 *
 * @ingroup views_field_handlers
 */
class og_views_handler_field_og_is_admin extends views_handler_field {
  function render($values) {
     $is_admin = $values->{$this->field_alias};
    // don't use og_is_group_admin() here since we don't want mark on 'admin nodes' people.
    if ($is_admin) {
      return t('admin'); 
    }
  }
}