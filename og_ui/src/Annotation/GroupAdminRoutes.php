<?php

/**
 * @file
 * Contains Drupal\og_ui\Annotation\GroupAdminRoutes.
 */

namespace Drupal\og_ui\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define an OG ui group admin routes.
 *
 * @Annotation
 */
class GroupAdminRoutes extends Plugin {

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

  /**
   * callable method to check if the user is allowed. The method will invoke the
   * plugin access method.
   *
   * Optional. Default value is \Drupal\og_ui\OgUiRoutesBase::access.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $access;

  /**
   * OG group permission which allowed access to the plugin tasks.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $permission;

  /**
   * The name of the route ID. Will be attached to the parents route ID.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $route_id;

  /**
   * List of routes IDs to display the group tab.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $parents_routes;

}
