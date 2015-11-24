<?php
/**
 * Contains
 */

namespace Drupal\og;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

interface OgFieldsInterface {

  /**
   * @return array
   *   Array that will be used as the base values for
   *   FieldStorageConfig::create().
   */
  public function fieldStorageConfigBaseDefinition();

  /**
   * @return array
   *   Array that will be used as the base values for
   *   FieldConfig::create().
   */
  public function fieldConfigBaseDefinition();

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
