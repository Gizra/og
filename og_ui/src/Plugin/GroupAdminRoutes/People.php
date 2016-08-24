<?php

namespace Drupal\og\Plugin\GroupAdminRoutes;

use Drupal\og\OgAdminRouteAbstract;
use Drupal\og\OgAdminRouteInterface;

/**
 * Manage people in the group.
 *
 * @OgAdmin(
 *   id = "people",
 *   path = "people",
 *   title = @Translation("People"),
 *   description = @Translation("Manage the group's members")
 * )
 */
class People extends OgAdminRouteAbstract {

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {

    return [
      OgAdminRouteInterface::MAIN => [
        'controller' => '\Drupal\og_ui\Controller\PeopleController::PeopleList',
        'sub_path' => 'manage',
        'title' => 'People',
      ],
      'add' => [
        'controller' => '\Drupal\og_ui\Controller\PeopleController::addPeopleForm',
        'sub_path' => 'add',
        'title' => 'Add people',
        'type' => 'local_action',
      ],
    ];
  }

}
