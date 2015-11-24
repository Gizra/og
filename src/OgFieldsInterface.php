<?php
/**
 * Contains
 */

namespace Drupal\og;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

interface OgFieldsInterface {

  /**
   * @param array $values
   *   The base values, to which the entity type and field name would be added.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldStorageConfig::create().
   */
  public function getFieldStorageConfigBaseDefinition(array $values = array());

  /**
   * @param array $values
   *   The base values, to which the entity type, bundle and field name would be
   *   added.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldConfig::create().
   */
  public function getFieldConfigBaseDefinition(array $values = array());

  /**
   * @return array
   *   A widget definition for the field.
   */
  public function widgetDefinition();

  /**
   * @return
   *   Return view modes entities for the field.
   */
  public function viewModesDefinition();
}
