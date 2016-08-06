<?php

namespace Drupal\og\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a OG group resolver item annotation object.
 *
 * @see \Drupal\og\Plugin\GroupResolverManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupResolver extends Plugin {

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
