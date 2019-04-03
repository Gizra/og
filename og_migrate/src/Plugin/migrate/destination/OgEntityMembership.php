<?php

namespace Drupal\og_migrate\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrates group content into the appropriate group content field.
 *
 * @MigrateDestination(
 *   id = "og_entity_membership"
 * )
 */
class OgEntityMembership extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The {entity_type.manager} service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Initialize method.
   *
   * @var array $configuration
   *   The plugin configuration.
   * @var string $plugin_id
   *   The plugin ID.
   * @var mixed $plugin_definition
   *   The plugin definition.
   * @var \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $entity_type = $row->getDestinationProperty('entity_type');
    $entity_id = $row->getDestinationProperty('entity_id');
    $langcode = $row->getDestinationProperty('language');

    /** @var \Drupal\Core\Entity\EntityStorageInterface $entityStorage */
    $entityStorage = $this->entityTypeManager->getStorage($entity_type);

    // Works around https://www.drupal.org/project/drupal/issues/3008202 by
    // refreshing entity cache for each entity migrated.
    $entityStorage->resetCache([$entity_id]);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entityStorage->load($entity_id);

    $field_name = $row->getDestinationProperty('field_name');
    $target_id = $row->getDestinationProperty('target_id');

    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }
    elseif ($entity->{$field_name}) {
      $entity->{$field_name}[] = ['target_id' => $target_id];
    }
    else {
      try {
        $entity->set($field_name, [$target_id]);
      }
      catch (\InvalidArgumentException $e) {
        return FALSE;
      }
    }

    $entity->save();

    return [
      'target_id' => $entity->{$field_name}->target_id,
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'target_id' => [
        'type' => 'integer',
      ],
      'entity_id' => [
        'type' => 'integer',
      ],
      'entity_type' => [
        'type' => 'string',
        'length' => 32,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'target_id' => $this->t('Target ID'),
      'entity_id' => $this->t('Entity ID'),
      'entity_type' => $this->t('Entity Type'),
      'field_name' => $this->t('Group Audience Field'),
      'language' => $this->t('Language Code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')
    );
  }

}
