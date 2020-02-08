<?php

namespace Drupal\og\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a OgDeleteOrphans annotation object.
 *
 * @Annotation
 */
class OgDeleteOrphans extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
