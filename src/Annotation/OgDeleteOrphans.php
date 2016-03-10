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
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * An integer to determine the weight of this plugin.
   *
   * This is used to show the plugin relative to other plugins in the
   * administration form.
   *
   * @var int optional
   */
  public $weight = NULL;

}
