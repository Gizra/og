<?php

namespace Drupal\og_ui;

class OgUi {

  /**
   * Get all the admin routes plugins.
   *
   * @return OgUiAdminRouteInterface[]
   */
  public static function getGroupAdminPlugins() {
    /** @var OgUiAdminRoutesPluginManager $plugins */
    $plugin_manager = \Drupal::service('plugin.manager.og_ui.group_admin_route');

    $plugins = [];
    foreach ($plugin_manager->getDefinitions() as $definition) {
      $plugins[$definition['id']] = $plugin_manager->createInstance($definition['id']);
    }

    return $plugins;
  }

  /**
   * Get the current entity from the route.
   *
   * @return \Drupal\Core\Entity\ContentEntityBase
   */
  public static function getEntity() {
    $entity_type_id = \Drupal::routeMatch()
      ->getRouteObject()
      ->getOption('entity_type_id');

    return \Drupal::routeMatch()->getParameter($entity_type_id);
  }

}
