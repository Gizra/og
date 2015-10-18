<?php
/**
 * Contains
 */

namespace Drupal\og;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

interface OgFieldsInterface {

  /**
   * @param array $field
   *   (optional) Array with field definitions, to allow easier overriding by
   *   the caller.
   *
   * @return FieldStorageConfig
   *   Return a new object of a FieldStorageConfig instance.
   */
  public function fieldDefinition(array $field = []);

  /**
   * @param array $instance
   *   (optional) Array with instance definitions, to allow easier overriding by
   *   the caller.
   *
   * @return FieldConfig
   *   Return a new object of a FieldInstanceConfig instance.
   */
  public function instanceDefinition(array $instance = []);

  /**
   * @param array $widget
   *   (optional) Array with widget definitions, to allow easier overriding by
   *   the caller.
   *
   * @return array
   *   A widget definition for the field.
   */
  public function widgetDefinition(array $widget = []);

  /**
   * @param array $view_mode
   *   (optional) Array with view mode definitions, to allow easier overriding
   *   by the caller.
   *
   * @return
   *   Return view modes entities for the field.
   */
  public function viewModesDefinition(array $view_mode = []);
}
