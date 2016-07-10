<?php

namespace Drupal\og_ui;

use Drupal\Core\Controller\ControllerBase;

/**
 * Base class to for OG UI admin routes to based upon.
 */
class OgUiRoutesBase extends ControllerBase {

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
