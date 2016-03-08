<?php

/**
 * @file
 * Contains \Drupal\og\OgGroupAudienceHelper.
 */

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * OG audience field helper methods.
 */
class OgGroupAudienceHelper {

  /**
   * The default OG audience field name.
   */
  const DEFAULT_FIELD = 'og_group_ref';

  /**
   * Return TRUE if a field can be used and has not reached maximum values.
   *d
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to check the field cardinality for.
   * @param string $field_name
   *   The field name to check the cardinality of.
   *
   * @return bool
   *
   * @throws \Drupal\Core\Field\FieldException
   */
  public static function checkFieldCardinality(ContentEntityInterface $entity, $field_name) {
    $field_definition = $entity->getFieldDefinition($field_name);

    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();

    if (!$field_definition) {
      throw new FieldException("No field with the name $field_name found for $bundle_id $entity_type_id entity.");
    }

    if (!Og::isGroupAudienceField($field_definition)) {
      throw new FieldException("$field_name field on $bundle_id $entity_type_id entity is not an audience field.");
    }

    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();

    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return TRUE;
    }

    return $entity->get($field_name)->count() < $cardinality;
  }

  /**
   * Returns the first group audience field that matches the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The group content to find a matching group audience field for.
   * @param string $group_type
   *   The group type that should be referenced by the group audience field.
   * @param string $group_bundle
   *   The group bundle that should be referenced by the group audience field.
   * @param bool $check_access
   *   (optional) Set this to FALSE to not check if the current user has access
   *   to the field. Defaults to TRUE.
   *
   * @return string|NULL
   *   The name of the group audience field, or NULL if no matching field was
   *   found.
   */
  public static function getMatchingField(ContentEntityInterface $entity, $group_type, $group_bundle, $check_access = TRUE) {
    $fields = Og::getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle());

    // Bail out if there are no group audience fields.
    if (!$fields) {
      return NULL;
    }

    foreach ($fields as $field_name => $field) {
      $handler_settings = $field->getSetting('handler_settings');

      if ($field->getSetting('target_type') !== $group_type) {
        // Group type doesn't match.
        continue;
      }

      if (!empty($handler_settings['target_bundles']) && !in_array($group_bundle, $handler_settings['target_bundles'])) {
        // Bundle doesn't match.
        continue;
      }

      if (!static::checkFieldCardinality($entity, $field_name)) {
        // The field cardinality has reached its maximum
        continue;
      }

      if ($check_access && !$entity->get($field_name)->access('view')) {
        // The user doesn't have access to the field.
        continue;
      }

      return $field_name;
    }

    return NULL;
  }

  /**
   * Get list of available widgets.
   *
   * @return array
   *   List of available entity reference widgets.
   */
  public static function getAvailableWidgets() {
    $widget_manager = \Drupal::getContainer()->get('plugin.manager.field.widget');
    $definitions = $widget_manager->getDefinitions();

    $widgets = [];
    foreach ($definitions as $id => $definition) {

      if (!in_array('entity_reference', $definition['field_types'])) {
        continue;
      }

      $widgets[] = $id;
    }

    return $widgets;
  }

  /**
   * Set the field mode widget.
   *
   * @param $entity_id
   *   The entity id.
   * @param $bundle
   *   The bundle.
   * @param $field_name
   *   The field name.
   * @param array $modes
   *   The field modes. Available keys: default, admin.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  public static function setWidgets($entity_id, $bundle, $field_name, array $modes) {
    $field = FieldConfig::loadByName($entity_id, $bundle, $field_name);
    $handler = $field->getSetting('handler_settings');
    $handler['handler_settings']['widgets'] = $modes;
    $field->setSetting('handler_settings', $handler);
    return $field->save();
  }

  /**
   * get the field mode widget.
   *
   * @param $entity_id
   *   The entity id.
   * @param $bundle
   *   The bundle.
   * @param $field_name
   *   The field name.
   * @param null $mode
   *   The field mode - admin or default.
   *
   * @return array.
   *   The field modes.
   */
  public static function getWidgets($entity_id, $bundle, $field_name, $mode = NULL) {
    $field = FieldConfig::loadByName($entity_id, $bundle, $field_name);
    $handler = $field->getSetting('handler_settings');
    return $mode ? $handler['handler_settings']['widgets'][$mode] : $handler['handler_settings']['widgets'];
  }

  /**
   * @param $entity_id
   *   The enitty type ID.
   * @param $bundle
   *   The entity bundle.
   * @param $field_name
   *   The field name.
   * @param $values
   *   The default values of the field.
   * @param $widget_id
   *   The field mode - admin or default.
   *
   * @return WidgetBase
   *   The form API widget element.
   */
  public static function renderWidget($entity_id, $bundle, $field_name, $widget_id, $values) {
    $config = FieldConfig::load($entity_id . '.' . $bundle . '.' . $field_name);
    $configuration = [
      'type' => 'og_complex',
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'placeholder' => '',
      ],
      'weight' => 1,
      'third_party_settings' => [],
      'field_definition' => $config,
    ];


    $field = \Drupal::getContainer()->get('plugin.manager.field.widget');

    return $field->createInstance($widget_id, $configuration);
  }

}
