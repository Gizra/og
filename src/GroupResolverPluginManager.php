<?php

namespace Drupal\og;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for GroupResolver plugins.
 */
class GroupResolverPluginManager extends DefaultPluginManager {

  /**
   * Constructs a GroupResolverPluginManager.
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
    parent::__construct('Plugin/GroupResolver', $namespaces, $module_handler, 'Drupal\og\OgContextInterface', 'Drupal\og\Annotation\GroupResolver');

    $this->alterInfo('og_group_resolver_info');
    $this->setCacheBackend($cache_backend, 'og_group_resolver');
  }

}
