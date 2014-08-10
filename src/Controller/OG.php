<?php

namespace Drupal\og\Controller;

use Drupal\field\Entity\FieldStorageConfig;

class OG {

  /**
   * Create an organic groups field in a bundle.
   *
   * @param $field_name
   *   The field name
   * @param $entity_type
   *   The entity type
   * @param $bundle
   *   The bundle name.
   * @param $og_field
   *   (optional) Array with field definitions, to allow easier overriding by
   *   the caller. If empty, function will get the field info by calling
   *   OG::FieldsInfo() with the field name.
   */
  public static function CreateField($field_name, $entity_type, $bundle, $og_field = array()) {
    if (empty($og_field)) {
      $og_field = self::FieldsInfo($field_name);
    }

    $field = FieldStorageConfig::load($field_name);
    // Allow overriding the field name.
    $og_field['field']['field_name'] = $field_name;
    if (empty($field)) {
      $og_field['field']->save();
    }

    $instance = field_info_instance($entity_type, $field_name, $bundle);
    if (empty($instance)) {
      $instance = $og_field['instance'];
      $instance += array(
        'field_name' => $field_name,
        'bundle' => $bundle,
        'entity_type' => $entity_type,
      );

      field_create_instance($instance);
      // Clear the entity property info cache, as OG fields might add different
      // entity property info.
      og_invalidate_cache();
      entity_property_info_cache_clear();
    }
  }

  /**
   * Get all the modules fields that can be assigned to fieldable entities.
   *
   * @param $field_name
   *   The field name that was registered for the definition.
   *
   * @return bool
   *   An array with the field and instance definitions, or FALSE if not
   */
  function FieldsInfo($field_name = NULL) {
    $config = \Drupal::service('plugin.manager.og.fields');
    $fields_config = $config->getDefinitions();

    if ($field_name) {
      return isset($fields_config[$field_name]) ? $config->createInstance($field_name) : NULL;
    }

    return $fields_config;
  }
}
