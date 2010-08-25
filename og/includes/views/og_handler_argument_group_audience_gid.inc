<?php
// $Id:$
/**
 * @file
 * Contains the group argument for field group audience.
 */

/**
 * Field group audience - gid argument handler.
 */
class og_handler_argument_group_audience_gid extends views_handler_argument_numeric {

  /**
   * Override title(); Get the label of the entity type.
   */
  function title() {
    $title = '';
    if ($group = og_context()) {

      // TODO: Use wrapper function.
      $title = check_plain($group->label);
    }

    return $title;
  }

}
