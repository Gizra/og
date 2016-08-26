<?php

namespace Drupal\og;

/**
 * @file
 * Contains \Drupal\og\GroupResolverHandler.
 */

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class GroupResolverHandler.
 *
 * @package Drupal\og
 */
class GroupResolverHandler implements GroupResolverHandlerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OG group resolver manager.
   *
   * @var \Drupal\og\GroupResolverManager
   */
  protected $pluginManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs an group resolver service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\og\GroupResolverManager $context_manager
   *   The OG context manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GroupResolverManager $context_manager, EntityTypeManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->pluginManager = $context_manager;
    $this->storage = $entity_manager->getStorage('group_resolver_negotiation');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    $plugins = $this->getPlugins();

    $groups = [];

    foreach ($plugins as $plugin) {
      if ($group = $this->getPlugin($plugin['id'])->getGroup()) {
        $groups = array_merge($groups, [$group]);
      }
    }

    // Return the first group for now. handle in a follow up PR to find the best
    // matching group.
    return reset($groups);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($return_mode = GroupResolverHandlerInterface::RETURN_ONLY_ACTIVE) {

    /** @var \Drupal\og\Entity\GroupResolverNegotiation[] $group_resolver_config */
    $group_resolver_config = $this->storage->loadMultiple();

    $plugins = $this->pluginManager->getDefinitions();

    if ($return_mode != GroupResolverHandlerInterface::RETURN_ALL) {

      foreach ($group_resolver_config as $context) {
        if ($return_mode == GroupResolverHandlerInterface::RETURN_ONLY_ACTIVE) {
          $condition = $context->get('status') == FALSE;
        }
        else {
          $condition = !in_array($context->id(), array_keys($plugins));
        }

        if ($condition) {
          unset($plugins[$context->id()]);
        }
      }
    }

    if (!empty($group_resolver_config)) {
      uasort($plugins, function ($a, $b) use ($group_resolver_config) {
        return $group_resolver_config[$a['id']]->get('weight') > $group_resolver_config[$b['id']]->get('weight') ? 1 : -1;
      });
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
  public function updatePlugin($plugin_id, $config = []) {
    /** @var GroupResolverNegotiation $contex */
    $context = $this->storage->load($plugin_id);

    foreach ($config as $key => $value) {
      $context->set($key, $value);
    }

    $context->save();
  }

  /**
   * {@inheritdoc}
   */
  public function updateConfigStorage() {
    $plugins = $this->getPlugins(GroupResolverHandlerInterface::RETURN_ALL);

    $group_resolver_storage = $this->storage;
    $group_resolver_config = $group_resolver_storage->loadMultiple();

    $weight = 0;
    foreach ($plugins as $plugin) {
      if (in_array($plugin['id'], array_keys($group_resolver_config))) {
        // The negotiation plugin already registered.
        continue;
      }

      // Registering a new negotiation plugin.
      $group_resolver_storage->create([
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
