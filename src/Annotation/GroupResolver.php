<?php

namespace Drupal\og\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a OG context item annotation object.
 *
 * @see \Drupal\og_context\Plugin\OgContextManager
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
