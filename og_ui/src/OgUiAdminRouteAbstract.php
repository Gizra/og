<?php

namespace Drupal\og_ui;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

abstract class OgUiAdminRouteAbstract extends PluginBase implements OgUiAdminRouteInterface {

  /**
   * @var ContentEntityBase
   */
  protected $group;

  /**
   * @inheritDoc
   */
  public function getGroup() {
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup(ContentEntityBase $group) {
    $this->group = $group;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getPath() {
    return $this->pluginDefinition['path'];
  }

  /**
   * @inheritDoc
   */
  public function getRoutes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute($key) {
    $routes = $this->getRoutes();

    return $routes[$key];
  }

  /**
   * @inheritDoc
   */
  public function getUrlFromRoute($route_key, Request $request) {
    $route = $this->getRoute($route_key);

    $route_info = [
      '/' . Url::createFromRequest($request)->toString(),
      $this->pluginDefinition['path'],
      $route['sub_path']
    ];

    return Url::fromUserInput(implode('/', $route_info));
  }

}
