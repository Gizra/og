<?php

namespace Drupal\og;

use Drupal\Component\Plugin\PluginInspectionInterface;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines an interface for OG context plugins.
 */
interface OgContextInterface extends PluginInspectionInterface {

  /**
   * Return the current group. If no group will be found return Null.
   *
   * @return NULL|ContentEntityBase
   */
  public function getGroup();

}
