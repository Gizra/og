<?php

namespace Drupal\og\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when OG admin routes are being compiled.
 */
class OgAdminRoutesEvent extends Event implements OgAdminRoutesEventInterface {

  /**
   * The routes info array.
   *
   * @var array
   */
  protected $routesInfo = [];

  /**
   * {@inheritdoc}
   */
  public function setRoutesInfo(array $routes_info) {
    $this->routesInfo = $routes_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutesInfo() {
    return $this->routesInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes($entity_type_id) {
    $routes_info = [];

    foreach ($this->routesInfo as $name => $route_info) {

      $routes_info[$name] = $route_info;

      // Add default values.
      $routes_info[$name] += [
        'description' => '',

        'requirements' => [
          '_og_user_access_group' => 'administer group',
        ],

        'options' => [
          'parameters' => [
            $entity_type_id => ['type' => 'entity:' . $entity_type_id],
          ],
          // The above parameters doesn't send the entity,
          // so we will have to use the Route matcher to extract it.
          '_og_entity_type_id' => $entity_type_id,
          '_admin_route' => TRUE,
        ],

        // Move the title and controller under the "defaults" key.
        'defaults' => [
          '_controller' => $route_info['controller'],
          '_title' => $route_info['title'],
        ],
      ];
    }

    return $routes_info;
  }

}
