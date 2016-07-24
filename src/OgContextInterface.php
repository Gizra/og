<?php

namespace Drupal\og;

/**
 * @file
 * Contains \Drupal\og\OgContextInterface.
 */

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for OG context plugins.
 */
interface OgContextInterface extends PluginInspectionInterface {

  /**
   * Return the current group. If no group will be found return Null.
   *
   * @return ContentEntityBase
   *   Return the best matching group.
   */
  public function getGroup();

}
