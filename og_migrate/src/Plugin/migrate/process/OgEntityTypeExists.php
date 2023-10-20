<?php

namespace Drupal\og_migrate\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirms that an entity_type exists in the destination.
 *
 * @MigrateProcessPlugin(
 *   id = "og_entity_type_exists"
 * )
 *
 * @internal
 */
class OgEntityTypeExists extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $bundle_prop = isset($this->configuration['bundle_property']) ? $this->configuration['bundle_property'] : FALSE;
    try {
      $storage = $this->entityTypeManager->getStorage($value);

      // Find the bundle because it's necessary for field_instance lookups.
      $gid = $row->getSourceProperty('gid');
      $entity = $storage->load($gid);

      if ($bundle_prop) {
        $row->setDestinationProperty($bundle_prop, $entity->bundle());
      }

      return $value;
    }
    catch (PluginNotFoundException $e) {
      return FALSE;
    }
    catch (PluginException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

}
