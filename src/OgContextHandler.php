<?php

namespace Drupal\og;

use Drupal\Core\Config\ConfigFactoryInterface;

class OgContextHandler implements OgContextHandlerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OG context manager.
   *
   * @var \Drupal\og\OgContextManager
   */
  protected $pluginManager;

  /**
   * Constructs an OgManager service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\og\OgContextManager $context_manager
   *   The OG context manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OgContextManager $context_manager) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $context_manager;
  }

  /**
   * Get a list of an OG context plugins.
   *
   * @return array
   */
  public function getPlugins() {
    return $this->pluginManager->getDefinitions();
  }

  /**
   * Create an instance of an OG context plugin.
   *
   * @param $plugin_id
   *
   * @return OgContextBase
   */
  public function getPlugin($plugin_id) {
    return $this->pluginManager->createInstance($plugin_id);
  }

}