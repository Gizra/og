<?php

namespace Drupal\og;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\og\Entity\OgContextNegotiation;

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
   * {@inheritdoc}
   */
  public function getPlugins($config = []) {
    $config += [
      // sort the plugins by the weight defined in negotiation schema.
      'sort_by_weight' => TRUE,
      'return_mode' => OgContextHandlerInterface::RETURN_ONLY_ACTIVE,
    ];

    $plugins = $this->pluginManager->getDefinitions();

    if ($config['return_mode'] != OgContextHandlerInterface::RETURN_ALL) {

      /** @var OgContextNegotiation[] $og_context_config */
      $og_context_config = \Drupal::entityTypeManager()->getStorage('og_context_negotiation')->loadMultiple();

      foreach ($og_context_config as $context) {
        if ($config['return_mode'] == OgContextHandlerInterface::RETURN_ONLY_ACTIVE) {
          $condition = $context->get('status') === FALSE;
        }
        else {
          $condition = !in_array($context->id(), array_keys($plugins));
        }

        if ($condition) {
          unset($plugins[$context->id()]);
        }
      }
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin($plugin_id) {
    return $this->pluginManager->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function updatePlugin($config = []) {

  }

  public function updateConfigStorage() {
    $plugins = $this->getPlugins(['return_mode' => OgContextHandlerInterface::RETURN_ALL]);

    // todo user storage injection.
    $og_context_storage = \Drupal::entityTypeManager()->getStorage('og_context_negotiation');
    $og_context_config = $og_context_storage->loadMultiple();

    $weight = 0;
    foreach ($plugins as $plugin) {
      if (in_array($plugin['id'], array_keys($og_context_config))) {
        // The negotiation plugin already registered.
        continue;
      }

      // Registering a new negotiation plugin.s
      $og_context_storage->create([
        'id' => $plugin['id'],
        'label' => $plugin['label'],
        'description' => $plugin['description'],
        'status' => FALSE,
        'weight' => $weight,
      ])->save();

      $weight++;
    }
  }

}