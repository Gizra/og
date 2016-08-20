<?php

namespace Drupal\og_ui;

/**
 * Class OgUi.
 */
class OgUi {

  /**
   * Get all the admin routes plugins.
   *
   * @return OgUiAdminRouteInterface[]
   *   Get all the OG tasks plugins.
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
   *   Get the current group form the route.
   */
  public static function getEntity() {
    // Un used for unit testing due to the fact that in unit testing the route
    // options does not contain the current viewed entity.
    // todo: find a better solution.
    $options = \Drupal::routeMatch()
      ->getRouteObject()
      ->getOptions();

    $entity_type_id = '';
    foreach ($options as $option) {
      if (!is_array($option)) {
        continue;
      }

      $item = reset($option);

      if (strpos($item['type'], 'entity:') === 0) {
        $entity_type_id = key($option);
        break;
      }
    }

    return \Drupal::routeMatch()->getParameter($entity_type_id);
  }

}
