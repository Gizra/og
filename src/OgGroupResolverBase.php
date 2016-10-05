<?php

namespace Drupal\og;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for OgGroupResolver plugins.
 */
abstract class OgGroupResolverBase extends PluginBase implements OgGroupResolverInterface {

  /**
   * Whether the group resolving process can be stopped.
   *
   * @var bool
   */
  protected $propagationStopped = FALSE;

  /**
   * {@inheritdoc}
   */
  public function stopPropagation() {
    $this->propagationStopped = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isPropagationStopped() {
    return $this->propagationStopped;
  }

}
