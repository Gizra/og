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

  /**
   * The type of resolver.
   *
   * @var string
   *   The resolver type. Can be one of the following:
   *   - 'provider': The plugin provides new groups that were discovered in
   *     their domain. For example the plugin might discover a group in a route
   *     object.
   *   - 'selector': The plugin doesn't provide groups of their own but helps to
   *     choose the right group from the groups that were discovered by other
   *     plugins. For example the plugin might inspect the user session to check
   *     if the user is coming from a group page.
   */
  public $type;

}
