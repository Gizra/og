<?php
/**
 * Base class for OG field plugin.
 */

namespace Drupal\og;

use Drupal\Core\Plugin\PluginBase;

abstract class OgFieldBase extends PluginBase implements OgFieldsInterface {

  /**
   * @var String
   *
   * The entity type.
   */
  protected $entityType;

  /**
   * @var String
   *
   * The entity bundle.
   */
  protected $bundle;

  /**
   * Set the entity type.
   *
   * @param String $entity_type
   *   The entity type.
   *
   * @return $this.
   */
  public function setEntityType($entity_type) {
    $this->entityType = $entity_type;

    return $this;
  }

  /**
   * @return String
   *   The entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * @param String $bundle
   *   The entity bundle.
   *
   * @return $this.
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;

    return $this;
  }

  /**
   * @return String
   *   The entity bundle.
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_definition, $plugin_definition);

    $this
      ->setEntityType($configuration['entity_type'])
      ->setBundle($configuration['bundle']);
  }
}
