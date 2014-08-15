<?php

namespace Drupal\og\Controller;

use Drupal\entity\Entity\EntityFormDisplay;
use Drupal\og\OgFieldBase;

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
   */
  public static function CreateField($field_name, $entity_type, $bundle) {
      $og_field = self::FieldsInfo($field_name)
        ->setEntityType($entity_type)
        ->setBundle($bundle);

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

    // Add the field to the form display manager.
    $displayForm = EntityFormDisplay::load($entity_type . '.' . $bundle . '.default');
    if (!$displayForm->getComponent($field_name) && $widgetDefinition = $og_field->widgetDefinition()) {
      $displayForm->setComponent($field_name, $widgetDefinition);
      $displayForm->save();
    }

    // Define the view mode for the field.
    if ($fieldViewModes = $og_field->viewModesDefinition()) {
      $viewModes = entity_load_multiple('entity_view_display', array_keys($fieldViewModes));

      foreach ($viewModes as $key => $viewMode) {
        $viewMode->setComponent($field_name, $fieldViewModes[$key])->save();
      }
    }
  }

  /**
   * Get all the modules fields that can be assigned to fieldable entities.
   *
   * @param $field_name
   *   The field name that was registered for the definition.
   *
   * @return OgFieldBase|bool
   *   An array with the field and instance definitions, or FALSE if not.
   *
   * todo: pass the entity type and entity bundle to plugin definition.
   */
  public static function FieldsInfo($field_name = NULL) {
    $config = \Drupal::service('plugin.manager.og.fields');
    $fields_config = $config->getDefinitions();

    if ($field_name) {
      return isset($fields_config[$field_name]) ? $config->createInstance($field_name) : NULL;
    }

    return $fields_config;
  }
}
