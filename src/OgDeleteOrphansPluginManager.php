<?php

namespace Drupal\og;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for OgDeleteOrphans plugins.
 */
class OgDeleteOrphansPluginManager extends DefaultPluginManager {

  /**
   * Constructs an OgDeleteOrphansPluginManager object.
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
    parent::__construct('Plugin/OgDeleteOrphans', $namespaces, $module_handler, 'Drupal\og\OgDeleteOrphansInterface', 'Drupal\og\Annotation\OgDeleteOrphans');
    $this->setCacheBackend($cache_backend, 'og_delete_orphans');
    $this->alterInfo('og_delete_orphans');
  }

}
