<?php
/**
 * Contains
 */

namespace Drupal\og;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

interface OgFieldsInterface {

  /**
   * Get the field identifier.
   *
   * The field identifier is often the field name that will be used, however it
   * is overridable. For example, the group audience field is identified as
   * OG_AUDIENCE_FIELD, however the actual field name attached to the bundle can
   * be arbitrary.
   *
   * @return string
   */
  public function getFieldIdentifier();

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
