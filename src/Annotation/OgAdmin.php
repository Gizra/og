<?php

namespace Drupal\og\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define an OG admin annotation plugin.
 *
 * @Annotation
 */
class OgAdmin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The route base of the plugin after the /group of the available tasks page.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $path;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * A short description of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
