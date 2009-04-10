<?php
/**
 * Field handler to show if user is manager or not of the displayed group node.
 *
 * @ingroup views_field_handlers
 */
class og_views_handler_field_og_is_manager extends views_handler_field {
  
  function render($values) {
    $member = $values->uid;
    
    // Tried to use additional fields to get node.uid but ultimately just used this.
    $node = node_load($values->og_uid_nid);
    $manager = $node->uid;

    if ($member == $manager) {
      return '<em>'. t('Manager'). '</em>'; 
    }
  }
}