<?php

namespace Drupal\og_ui;

class OgUi {

  /**
   * Get all the admin routes plugins.
   *
   * @return OgUiAdminRoutesPluginManager
   */
  public static function getGroupAdminPlugins() {
    return \Drupal::service('plugin.manager.og_ui.group_admin_route');
  }

  /**
   * Get the current entity from the route.
   *
   * @return \Drupal\Core\Entity\ContentEntityBase
   */
  public static function getEntity() {
    $route_match = \Drupal::routeMatch();
    $parameters = $route_match->getParameters();
    reset($parameters->keys());
  }

}
