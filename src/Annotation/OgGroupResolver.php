<?php

namespace Drupal\og\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the OgGroupResolver annotation object.
 *
 * @see \Drupal\og\OgGroupResolverPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class OgGroupResolver extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
