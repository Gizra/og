<?php

namespace Drupal\og_migrate\Plugin\migrate;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 7 og membership migrations.
 */
class OgEntityDeriver extends DeriverBase implements ContainerDeriverInterface {
  use MigrationDeriverTrait;

  /**
   * The base plugin ID of the migration.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity_type.manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin.manager.migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * Initialization method.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationManager
   *   The plugin.manager.migration service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entityTypeManager, MigrationPluginManagerInterface $migrationManager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entityTypeManager;
    $this->migrationManager = $migrationManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $supported_types = [
      'node' => 'd7_node',
      'user' => 'd7_user',
      'taxonomy_term' => 'd7_taxonomy_term',
    ];

    /** @var \Drupal\og_migrate\Plugin\migrate\source\d7\OgMembership $source */
    $source = static::getSourcePlugin('d7_og_membership');
    try {
      $source->checkRequirements();
      if (!($source instanceof ConfigurableInterface)) {
        throw new PluginException('Source plugin is not configurable.');
      }

      foreach (array_keys($supported_types) as $group_type) {
        if ($this->entityTypeManager->hasDefinition($group_type)) {
          $configuration = [
            'group_type' => $group_type,
          ];

          foreach (array_keys($supported_types) as $entity_type) {
            if ($this->entityTypeManager->hasDefinition($entity_type)) {
              $derivative = $group_type . '_' . $entity_type;
              $configuration['entity_type'] = $entity_type;
              $source->setConfiguration($configuration);

              $hasMemberships = $source
                ->query()
                ->countQuery()
                ->execute()
                ->fetchField();
              if ($hasMemberships) {
                $values = $base_plugin_definition;
                $values['label'] = t('@label (@group_type @entity_type)', [
                  '@label' => $values['label'],
                  '@group_type' => $group_type,
                  '@entity_type' => $entity_type,
                ]);

                // Sets migrate source plugin configuration.
                $values['source']['group_type'] = $group_type;
                $values['source']['entity_type'] = $entity_type;

                // Sets process plugins for group entity type and entity type
                // based on either an user or entity membership migration.
                if ($entity_type === 'user') {
                  $values['process'] = $this->getUserProcessDefinition($supported_types[$group_type]);
                  $values['destination']['plugin'] = 'entity:og_membership';
                  $values['migration_dependencies']['required'][] = 'd7_og_membership_type';
                  $values['migration_dependencies']['required'][] = 'd7_field_instance';
                  $values['migration_dependencies']['required'][] = 'd7_content';
                }
                else {
                  $values['process'] = $this->getEntityProcessDefinition($supported_types[$group_type], $supported_types[$entity_type]);
                  $values['migration_dependencies']['required'][] = 'd7_og_field_instance';
                }

                // Adds migration dependencies.
                $values['migration_dependencies']['required'][] = $supported_types[$group_type];
                $values['migration_dependencies']['required'][] = $supported_types[$entity_type];

                $migration = $this->migrationManager->createStubMigration($values);
                $this->derivatives[$derivative] = $migration->getPluginDefinition();
              }
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      // Return the remaining derivatives, if any.
    }

    return $this->derivatives;
  }

  /**
   * Gets the migration process definition for an entity_type.
   *
   * @param string $group_type_migration
   *   The group_type migration id.
   * @param string $entity_type_migration
   *   The entity_type migration id.
   *
   * @return array
   *   The "process" definition.
   */
  protected function getEntityProcessDefinition($group_type_migration, $entity_type_migration) {
    return [
      'target_id' => [
        [
          'plugin' => 'migration_lookup',
          'migration' => $group_type_migration,
          'source' => 'gid',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'Migration group is missing',
        ],
      ],
      'entity_id' => [
        [
          'plugin' => 'migration_lookup',
          'migration' => $entity_type_migration,
          'source' => 'etid',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'Migration entity is missing',
        ],
      ],
      'entity_type' => [
        [
          'plugin' => 'og_entity_type_exists',
          'source' => 'entity_type',
          'bundle_property' => 'bundle',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'Entity type is missing',
        ],
      ],
      'group_type' => [
        [
          'plugin' => 'og_entity_type_exists',
          'source' => 'group_type',
          'bundle_property' => 'bundle',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'Group type is missing',
        ],
      ],
      'field_name' => [
        'plugin' => 'static_map',
        'source' => 'field_name',
        'map' => [
          'og_group_ref' => 'og_audience',
          'default' => 'og_audience',
        ],
      ],
      'language' => 'language',
    ];
  }

  /**
   * Gets the migration process definition for user.
   *
   * @param string $group_type_migration
   *   The group_type migration id.
   *
   * @return array
   *   The "process" definition.
   */
  protected function getUserProcessDefinition($group_type_migration) {
    return [
      'id' => 'id',
      'type' => [
        [
          'plugin' => 'migration_lookup',
          'migration' => 'd7_og_membership_type',
          'source' => 'type',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'Membership type is missing',
        ],
      ],
      'uid' => [
        [
          'plugin' => 'migration_lookup',
          'migration' => 'd7_user',
          'source' => 'etid',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'User is missing',
        ],
      ],
      'entity_id' => [
        [
          'plugin' => 'migration_lookup',
          'migration' => $group_type_migration,
          'source' => 'gid',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'Migration group is missing',
        ],
      ],
      'entity_type' => [
        [
          'plugin' => 'og_entity_type_exists',
          'source' => 'group_type',
          'bundle_property' => 'entity_bundle',
        ],
        [
          'plugin' => 'skip_on_empty',
          'method' => 'row',
          'message' => 'Entity type is missing',
        ],
      ],
      'roles' => [
        'plugin' => 'migration_lookup',
        'migration' => 'd7_og_role',
        'source' => 'roles',
      ],
      'state' => [
        'plugin' => 'static_map',
        'source' => 'state',
        'map' => [
          '1' => 'active',
          '2' => 'pending',
          '3' => 'blocked',
        ],
        'default_value' => 'active',
      ],
      'created' => 'created',
      'language' => 'language',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration')
    );
  }

}
