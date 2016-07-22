<?php

namespace Drupal\og;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the OG context plugin manager.
 */
class OgContextManager extends DefaultPluginManager {

  /**
   * Constructor for OgContextManager objects.
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
    parent::__construct('Plugin/OgContext', $namespaces, $module_handler, 'Drupal\og\OgContextInterface', 'Drupal\og\Annotation\OgContext');

    $this->alterInfo('og_context_og_context_info');
    $this->setCacheBackend($cache_backend, 'og_context_og_context_plugins');
  }

}
