<?php

namespace Drupal\og;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation for OgDeleteOrphans plugins.
 */
abstract class OgDeleteOrphansBase implements OgDeleteOrphansInterface, ContainerFactoryPluginInterface {

  /**
   * A configuration array containing information about the plugin instance.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The plugin ID for the plugin instance.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin implementation definition.
   *
   * @var mixed
   */
  protected $pluginDefinition;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue of orphans to delete.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs an OgDeleteOrphansBase object.
   *
   * @var array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue_factory) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue_factory->get('og_orphaned_group_content', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function register(EntityInterface $entity) {
    foreach ($this->query($entity) as $entity_type => $orphans) {
      foreach ($orphans as $orphan) {
        $this->queue->createItem([
          'type' => $entity_type,
          'id'=> $orphan,
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query(EntityInterface $entity) {
    return Og::getGroupContent($entity);
  }

}
