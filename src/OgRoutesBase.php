<?php

namespace Drupal\og;

use Drupal\Core\Controller\ControllerBase;

/**
 * Base class to for OG UI admin routes to based upon.
 */
class OgRoutesBase extends ControllerBase {

  /**
   * Basic access method. Can be override by the access plugin attribute.
   */
  public static function access() {
    /** @var OgUiAdminRouteInterface $plugin */
    $plugin_id = \Drupal::routeMatch()
      ->getRouteObject()
      ->getRequirement('_plugin_id');

    $plugin = OgUi::getGroupAdminPlugins()[$plugin_id];

    return $plugin->setGroup(OgUi::getEntity())->access();
  }

}
