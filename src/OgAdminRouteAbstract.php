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
   * {@inheritdoc}
   */
  public function getSubRoutes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function access(ContentEntityInterface $group) {
    return $this->ogAccess->userAccess($group, $this->pluginDefinition['permission']);
  }

}
