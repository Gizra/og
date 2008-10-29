<?php
/**
 * Allow Group ID(s) as argument
 *
 * @ingroup views_argument_handlers
 */
class og_views_handler_argument_og_uid_nid extends views_handler_argument_numeric {
  // TODOL: set context?
  
  /**
   * Give an argument the opportunity to modify the breadcrumb, if it wants.
   * This only gets called on displays where a breadcrumb is actually used.
   *
   * The breadcrumb will be in the form of an array, with the keys being
   * the path and the value being the already sanitized title of the path.
   */
  function set_breadcrumb(&$breadcrumb) { 
    $nid = $this->value[0];
    // TODOL: not working.
    // $breadcrumb = og_views_set_breadcrumb($nid);
  }
}