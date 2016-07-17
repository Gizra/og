<?php

namespace Drupal\og_context;

use Drupal\og_context\Plugin\OgContextBase;
use Drupal\og_context\Plugin\OgContextManager;

class OgContext {

  /**
   * Get a instance of an OG context plugin.
   *
   * @param $plugin
   *   The plugin ID.
   *
   * @return OgContextBase
   */
  static public function getPlugin($plugin) {
    /** @var OgContextManager $service */
    $service = \Drupal::service('plugin.manager.og.context');

    return $service->createInstance($plugin);
  }

}
