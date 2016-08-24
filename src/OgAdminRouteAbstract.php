<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OgAdminRouteAbstract.
 */
abstract class OgAdminRouteAbstract extends PluginBase implements OgAdminRouteInterface, ContainerFactoryPluginInterface {

  /**
   * The OG access service.
   *
   * @var OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The current user service.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The account object.
   *
   * @var UserInterface
   */
  protected $account;

  /**
   * The group the plugin handle.
   *
   * @var ContentEntityBase
   */
  protected $group;

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
   * @param AccountProxyInterface $current_user
   *   The current user object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgAccessInterface $og_access, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogAccess = $og_access;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.access'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->pluginDefinition['path'];
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getUrlFromRoute($route_key, Request $request) {
    $route = $this->getRoute($route_key);

    $route_info = [
      '/' . Url::createFromRequest($request)->toString(),
      $this->pluginDefinition['path'],
      $route['sub_path'],
    ];

    return Url::fromUserInput(implode('/', $route_info));
  }

  /**
   * {@inheritdoc}
   */
  public function access(ContentEntityInterface $group) {
    return $this->ogAccess->userAccess($group, $this->pluginDefinition['permission']);
  }

}
