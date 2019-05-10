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
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * Constructs an OgDeleteOrphansBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager service.
   * @param \Drupal\og\OgGroupAudienceHelperInterface $group_audience_helper
   *   The OG group audience helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue_factory, MembershipManagerInterface $membership_manager, OgGroupAudienceHelperInterface $group_audience_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->membershipManager = $membership_manager;
    $this->groupAudienceHelper = $group_audience_helper;
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
      $container->get('queue'),
      $container->get('og.membership_manager'),
      $container->get('og.group_audience_helper')
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
          'id' => $orphan,
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
   *   An associative array, keyed by entity type, each item an array of entity
   *   IDs to delete.
   */
  protected function query(EntityInterface $entity) {
    // Register orphaned group content.
    $orphans = $this->membershipManager->getGroupContentIds($entity);

    // Register orphaned user memberships.
    $membership_ids = $this->entityTypeManager->getStorage('og_membership')
      ->getQuery()
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->execute();

    if (!empty($membership_ids)) {
      $orphans['og_membership'] = $membership_ids;
    }

    return $orphans;
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

    // The entity might already be removed by other modules that implement
    // hook_entity_delete().
    if (!$entity) {
      return;
    }

    // Only delete group content that is fully orphaned, i.e. it is no longer
    // associated with any groups.
    if ($this->groupAudienceHelper->hasGroupAudienceField($entity->getEntityTypeId(), $entity->bundle())) {
      // Only do a group count if the entity is actually group content.
      $group_count = $this->membershipManager->getGroupCount($entity);
      if ($group_count == 0) {
        $entity->delete();
      }
    }
    // If the entity is not group content (e.g. an OgMembership entity), just go
    // ahead and delete it.
    else {
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
  public function configurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

}
