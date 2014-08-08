<?php

namespace Drupal\og\Controller;

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
      $og_field = og_fields_info($field_name);
    }

    $field = field_info_field($field_name);
    // Allow overriding the field name.
    $og_field['field']['field_name'] = $field_name;
    if (empty($field)) {
      $field = field_create_field($og_field['field']);
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
    $return = &drupal_static(__FUNCTION__, array());

    if (empty($return)) {
      foreach (module_implements('og_fields_info') as $module) {
        if ($fields = module_invoke($module, 'og_fields_info')) {
          foreach ($fields as $key => $field) {
            // Add default values.
            $field += array(
              'entity type' => array(),
              'multiple' => FALSE,
              'description' => '',
            );

            // Add the module information.
            $return[$key] = array_merge($field, array('module' => $module));
          }
        }
      }

      // Allow other modules to alter the field info.
      drupal_alter('og_fields_info', $return);
    }

    if (!empty($field_name)) {
      return !empty($return[$field_name]) ?  $return[$field_name] : FALSE;
    }

    return $return;
  }
}