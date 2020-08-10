<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages OG fields plugins.
 */
class OgFieldsPluginManager extends DefaultPluginManager {

  /**
   * Constructs an OG field manager object.
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
    parent::__construct('Plugin/OgFields', $namespaces, $module_handler, NULL, 'Drupal\og\Annotation\OgFields');
    $this->alterInfo('og_fields_alter');
    $this->setCacheBackend($cache_backend, 'og_fields');
  }

}
