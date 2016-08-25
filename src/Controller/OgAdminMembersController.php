<?php

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * OgAdminMembersController class.
 */
class OgAdminMembersController extends ControllerBase {

  /**
   * Display list of people which belong to the group.
   */
  public function membersList() {
    return [
      '#markup' => 'membersList placeholder',
    ];
  }

  /**
   * Add people to the group.
   */
  public function addPeopleForm() {
    return [
      '#markup' => 'addPeopleForm placeholder',
    ];
  }

}
