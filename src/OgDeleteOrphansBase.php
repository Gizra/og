<?php

namespace Drupal\og;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation for OgDeleteOrphans plugins.
 */
abstract class OgDeleteOrphansBase extends PluginBase implements OgDeleteOrphansInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

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
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
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
        $this->getQueue()->createItem([
          'type' => $entity_type,
          'id'=> $orphan,
        ]);
      }
    }
  }

  /**
   * Queries the registered group entity for orphaned members to delete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity that is the basis for the query.
   *
   * @return array
   *   An associative array, keyed by group content entity type, each item an
   *   array of group content entity IDs to delete.
   */
  protected function query(EntityInterface $entity) {
    return Og::getGroupContentIds($entity);
  }

  /**
   * Deletes an orphaned group content entity if it is fully orphaned.
   *
   * @param string $entity_type
   *   The group content entity type.
   * @param string $entity_id
   *   The group content entity ID.
   */
  protected function deleteOrphan($entity_type, $entity_id) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    // Only delete content that is fully orphaned, i.e. it is no longer
    // associated with any groups.
    $group_count = Og::getGroupCount($entity);
    if ($group_count == 0) {
      $entity->delete();
    }
  }

  /**
   * Returns the queue of orphans to delete.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The queue.
   */
  protected function getQueue() {
    return $this->queueFactory->get('og_orphaned_group_content', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm($form, FormStateInterface $form_state) {
    return [];
  }

}
