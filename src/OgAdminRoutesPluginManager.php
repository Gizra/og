<?php

namespace Drupal\og;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manager for the OG admin plugins.
 */
class OgAdminRoutesPluginManager extends DefaultPluginManager {

  /**
   * Constructs an OG admin tasks manager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GroupAdminRoutes', $namespaces, $module_handler, NULL, 'Drupal\og_ui\Annotation\GroupAdminRoutes');
    $this->alterInfo('og_ui_group_admin_routes');
    $this->setCacheBackend($cache_backend, 'og_ui_group_admin_routes');
  }

  /**
   * Get all the admin routes plugins.
   *
   * @return OgAdminRouteInterface[]
   *   Get all the OG plugins.
   */
  public static function getGroupAdminPlugins() {
    /** @var OgAdminRoutesPluginManager $plugins */
    $plugin_manager = \Drupal::service('plugin.manager.og_ui.group_admin_route');

    $plugins = [];
    foreach ($plugin_manager->getDefinitions() as $definition) {
      $plugins[$definition['id']] = $plugin_manager->createInstance($definition['id']);
    }

    return $plugins;
  }

}
