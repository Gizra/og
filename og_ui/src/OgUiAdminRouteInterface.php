<?php

namespace Drupal\og_ui;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Symfony\Component\HttpFoundation\Request;

interface OgUiAdminRouteInterface extends PluginInspectionInterface{

  /**
   * Represent the main route key in the sub array routes.
   */
  const MAIN = 'main';

  /**
   * @return ContentEntityBase
   */
  public function getGroup();

  /**
   * @param ContentEntityBase $group
   *
   * @return OgUiAdminRouteInterface
   */
  public function setGroup(ContentEntityBase $group);

  /**
   * Get the path of the admin.
   *
   * @return string
   */
  public function getPath();

  /**
   * Check if the current user can access to the plugin routes callback.
   *
   * @return boolean
   */
  public function access();

  /**
   * Return list of defined sub-path of the plugin.
   *
   * @return array
   */
  public function getRoutes();

  /**
   * Get the routes easily.
   *
   * @param $key
   *   The key which represent the route in the array.
   *
   * @return array
   */
  public function getRoute($key);

  /**
   * Get URL route from request.
   *
   * @param $route_key
   *   The key of the route as defined in the route list array.
   * @param Request $request
   *   The current request object or a request object for a given route.
   *   Used to construct the path of the link.
   *
   * @return mixed
   */
  public function getUrlFromRoute($route_key, Request $request);

}
