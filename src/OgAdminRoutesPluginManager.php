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
    parent::__construct('Plugin/OgAdmin', $namespaces, $module_handler, NULL, 'Drupal\og\Annotation\OgAdmin');
    $this->alterInfo('og_admin');
    $this->setCacheBackend($cache_backend, 'og_admin');
  }

  /**
   * Get all the admin plugins.
   *
   * @return OgAdminRouteInterface[]
   *   An array with the OG admin plugins.
   */
  public function getPlugins() {
    $plugins = [];
    foreach ($this->getDefinitions() as $definition) {
      $plugins[$definition['id']] = $this->createInstance($definition['id']);
    }

    return $plugins;
  }

}
