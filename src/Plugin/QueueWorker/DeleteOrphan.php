<?php

namespace Drupal\og\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\og\OgDeleteOrphansPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes orphaned group content.
 *
 * @QueueWorker(
 *   id = "og_orphaned_group_content_cron",
 *   title = @Translation("Delete orphaned group content"),
 *   cron = {"time" = 60}
 * )
 */
class DeleteOrphan extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The plugin manager for OgDeleteOrphans plugins.
   *
   * @var \Drupal\og\OgDeleteOrphansPluginManager
   */
  protected $ogDeleteOrphansPluginManager;

  /**
   * Constructs a DeleteOrphan object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgDeleteOrphansPluginManager $og_delete_orphans_plugin_manager
   *   The plugin manager for OgDeleteOrphans plugins.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgDeleteOrphansPluginManager $og_delete_orphans_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogDeleteOrphansPluginManager = $og_delete_orphans_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.og.delete_orphans')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->ogDeleteOrphansPluginManager->createInstance('cron', [])->processItem($data);
  }

}
