<?php
// $Id$

/**
 * @file
 * Message type editing UI.
 */

/**
 * UI controller.
 */
class OgMembershipUIController extends EntityDefaultUIController {

  /**
   * Overrides hook_menu() defaults.
   */
  public function hook_menu() {
    $items = parent::hook_menu();
    $items[$this->path]['description'] = 'Manage group memberships, including fields.';
    return $items;
  }
}