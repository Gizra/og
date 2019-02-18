<?php

namespace Drupal\og_migrate\Plugin\migrate\destination;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\og\GroupTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Destination plugin for {og.settings.groups} configuration.
 *
 * @MigrateDestination(
 *   id = "og_group"
 * )
 */
class OgGroup extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The configuration array for the plugin.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->config = $configFactory->getEditable(GroupTypeManager::SETTINGS_CONFIG_KEY);
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $entity_type = $row->getDestinationProperty('entity_type');
    $bundle = $row->getDestinationProperty('bundle');
    $groups = $this->config->get(GroupTypeManager::GROUPS_CONFIG_KEY);

    if (!isset($groups[$entity_type])) {
      $groups[$entity_type] = [];
    }

    if (!in_array($bundle, $groups[$entity_type])) {
      $groups[$entity_type][] = $bundle;
    }
    else {
      return FALSE;
    }

    try {
      $this->config
        ->set('groups', $groups)
        ->save();
    }
    catch (ConfigValueException $e) {
      return FALSE;
    }

    return [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'entity_type' => $this->t('Entity Type'),
      'bundle' => $this->t('Bundle'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
        'length' => 32,
        'is_ascii' => TRUE,
      ],
      'bundle' => [
        'type' => 'string',
        'length' => 128,
        'is_ascii' => TRUE,
      ],
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
      $container->get('config.factory')
    );
  }

}
