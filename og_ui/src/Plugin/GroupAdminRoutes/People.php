<?php

namespace Drupal\og_ui\Plugin\GroupAdminRoutes;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og_ui\OgUiAdminRouteAbstract;
use Drupal\og_ui\OgUiAdminRouteInterface;

/**
 * @GroupAdminRoutes(
 *   id = "people",
 *   path = "people",
 *   title = @Translation("People"),
 *   description = @Translation("Manage the group's members"),
 *   access = "\Drupal\og_ui\Controller\PeopleController::access",
 *   route_id = "people",
 *   parents_routes = {
 *    "node" = "entity.node.canonical"
 *   }
 * )
 */
class People extends OgUiAdminRouteAbstract {

  /**
   * {@inheritdoc}
   */
  public function access() {
    return AccessResultAllowed::allowedIf(TRUE);
//    return AccessResultAllowed::allowedIf($this->getMembership()->hasPermission('administer group'));
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {

    return [
      OgUiAdminRouteInterface::MAIN => [
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
