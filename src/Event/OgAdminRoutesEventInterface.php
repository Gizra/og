<?php

namespace Drupal\og\Event;

/**
 * Interface for OgAdminRoutesEvent classes.
 *
 * This event allows implementing modules to provide their own OG Admin routes
 * or alter existing definitions that are provided by other modules.
 */
interface OgAdminRoutesEventInterface {

  /**
   * The event name.
   */
  const EVENT_NAME = 'og.og_admin_routes';

  /**
   * Set routes info.
   *
   * Array with the routes to create with the following keys:
   *   - title
   *   - controller
   *
   * @param array $routes_info
   *   The routes info array
   */
  public function setRoutes(array $routes_info);

  /**
   * Get routes.
   *
   * @return array
   *   Array with the routes info.
   */
  public function getRoutes();

}
