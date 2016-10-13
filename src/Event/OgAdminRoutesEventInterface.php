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
   *   - controller.
   *
   * @param array $routes_info
   *   The routes info array.
   */
  public function setRoutesInfo(array $routes_info);

  /**
   * Get routes info.
   *
   * @return array
   *   The routes info array.
   */
  public function getRoutesInfo();

  /**
   * Get routes.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   Array with the routes info.
   */
  public function getRoutes($entity_type_id);

}
