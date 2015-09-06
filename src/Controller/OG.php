<?php

namespace Drupal\og\Controller;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\Entity\OgMembership;
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

    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      $og_field->fieldDefinition()->save();
    }

    // Allow overriding the field name.
    // todo: ask if we need this.
//    $og_field['field']['field_name'] = $field_name;
//    if (empty($field)) {
//      $og_field['field']->save();
//    }

    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      $og_field->instanceDefinition()->save();
      // Clear the entity property info cache, as OG fields might add different
      // entity property info.
      self::invalidateCache();
    }

    // Add the field to the form display manager.
    $displayForm = EntityFormDisplay::load($entity_type . '.' . $bundle . '.default');
    if (!$displayForm->getComponent($field_name) && $widgetDefinition = $og_field->widgetDefinition()) {
      $displayForm->setComponent($field_name, $widgetDefinition);
      $displayForm->save();
    }

    // Define the view mode for the field.
    if ($fieldViewModes = $og_field->viewModesDefinition()) {
      $prefix = $entity_type . '.' . $bundle . '.';
      $viewModes = entity_load_multiple('entity_view_display', array_keys($fieldViewModes));

      foreach ($viewModes as $key => $viewMode) {
        $viewMode->setComponent($field_name, $fieldViewModes[$prefix . $key])->save();
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

  /**
   * Invalidate cache.
   *
   * @param $gids
   *   Array with group IDs that their cache should be invalidated.
   */
  public static function invalidateCache($gids = array()) {
    // Reset static cache.
    $caches = array(
      'og_user_access',
      'og_user_access_alter',
      'og_role_permissions',
      'og_get_user_roles',
      'og_get_permissions',
      'og_get_group_audience_fields',
      'og_get_entity_groups',
      'og_get_membership',
      'og_get_field_og_membership_properties',
      'og_get_user_roles',
    );

    foreach ($caches as $cache) {
      drupal_static_reset($cache);
    }

    // Let other OG modules know we invalidate cache.
    \Drupal::moduleHandler()->invokeAll('og_invalidate_cache', $gids);
  }

  /**
   * Check if the given entity is a group.
   *
   * @param EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   True or false if the given entity is group.
   */
  public static function IsGroup(EntityInterface $entity) {
    $definition = \Drupal::entityManager()->getDefinition($entity->getEntityTypeId());
    return $definition instanceof ContentEntityType && $entity->hasField(OG_GROUP_FIELD);
  }

  /**
   * Check if the given entity is a group content.
   *
   * @param EntityInterface $entity
   *   The entity object.
   *
   * @return Bool
   */
  public static function IsGroupContent(EntityInterface $entity) {
    $definition = \Drupal::entityManager()->getDefinition($entity->getEntityTypeId());
    return $definition instanceof ContentEntityType && $entity->hasField(OG_AUDIENCE_FIELD);
  }

  /**
   * Get the storage manage for the OG membership entity.
   *
   * @return OgMembership
   */
  public static function MembershipStorage() {
    return \Drupal::entityManager()->getStorage('og_membership');
  }

  /**
   * Get the default constructor parameters for OG membership.
   */
  public static function MembershipDefault() {
    return array('type' => 'og_membership_type_default');
  }

}
