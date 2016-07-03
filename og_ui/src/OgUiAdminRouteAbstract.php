<?php

namespace Drupal\og_ui;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\og\Entity\OgMembership;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class OgUiAdminRouteAbstract extends PluginBase implements OgUiAdminRouteInterface, ContainerFactoryPluginInterface {

  /**
   * @var OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param OgAccessInterface $og_access
   *   The OgAccess service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgAccessInterface $og_access) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.access')
    );
  }

  /**
   * @var ContentEntityBase
   */
  protected $group;

  /**
   * @inheritDoc
   */
  public function getGroup() {
    return $this->group;
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

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->ogAccess->userAccess($this->getGroup(), $this->pluginDefinition['permission']);
  }

}
