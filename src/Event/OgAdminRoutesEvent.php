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
  public function setRoutes(array $routes_info) {
    $this->routesInfo = $routes_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes() {
    return $this->routesInfo;
  }

}
