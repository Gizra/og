<?php

namespace Drupal\og\Controller;

use Drupal\og\OgFieldsInterface;

class OG {

  /**
   * Create an organic groups field in a bundle.
   *
   * @param $field_name
   *   The field name.
   * @param $entity_type
   *   The entity type.
   * @param $bundle
   *   The bundle name.
   * @param $og_field
   *   (optional) Array with field definitions, to allow easier overriding by
   *   the caller. If empty, function will get the field info by calling
   *   OG::FieldsInfo() with the field name.
   */
  public static function CreateField($field_name, $entity_type, $bundle, $og_field = array()) {
    if (empty($og_field)) {
      $og_field = self::FieldsInfo($field_name, $entity_type, $bundle);
    }

    $field = entity_load('field_storage_config', $entity_type . '.' . $og_field->fieldDefinition()->getName());

    if (!$field) {
      // The field storage config not exists. Create it.
      $og_field->fieldDefinition()->save();
    }

    // Allow overriding the field name.
    // todo: ask if we need this.
//    $og_field['field']['field_name'] = $field_name;
//    if (empty($field)) {
//      $og_field['field']->save();
//    }

    $instance = entity_load('field_instance_config', $entity_type . '.' . $bundle . '.' . $field_name);

    if (!$instance) {
      $og_field->instanceDefinition()->save();
      // Clear the entity property info cache, as OG fields might add different
      // entity property info.
//      og_invalidate_cache();
//      entity_property_info_cache_clear();
    }

    // todo: Create the widget of the field.
    // todo: Create the view modes.
  }

  /**
   * Get all the modules fields that can be assigned to fieldable entities.
   *
   * @param $field_name
   *   The field name that was registered for the definition.
   *
   * @return OgFieldsInterface|bool
   *   An array with the field and instance definitions, or FALSE if not.
   *
   * todo: pass the entity type and entity bundle to plugin definition.
   */
  public static function FieldsInfo($field_name = NULL, $entity_type = NULL, $bundle = NULL) {
    $config = \Drupal::service('plugin.manager.og.fields');
    $fields_config = $config->getDefinitions();

    if ($field_name) {
      return isset($fields_config[$field_name]) ? $config->createInstance($field_name, array('entity_type' => $entity_type, 'bundle' => $bundle)) : NULL;
    }

    return $fields_config;
  }
}
