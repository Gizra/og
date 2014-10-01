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
   * @return OgFieldBase
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
   * @return OgFieldBase
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

}
