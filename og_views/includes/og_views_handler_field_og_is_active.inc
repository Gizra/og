<?php
/**
 * Field handler to show if user is admin or not.
 *
 * @ingroup views_field_handlers
 */
class og_views_handler_field_og_is_active extends views_handler_field {
  function render($values) {
    $is_active = $values->{$this->field_alias};
    if (!$is_active) {
      return '<em>('. t('approval needed'). ')</em>'; 
    }
  }
}