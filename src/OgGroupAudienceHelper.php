<?php

/**
 * @file
 * Contains \Drupal\og\OgGroupAudienceHelper.
 */

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

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

    if (!$field_definition) {
      throw new FieldException(sprintf('No "%s" field found for %s %s entity', $field_name, $entity->bundle(), $entity->getEntityTypeId()));
    }

    if (!Og::isGroupAudienceField($field_definition)) {
      throw new FieldException(sprintf('"%s" field on %s %s entity is not an audience field.', $field_name, $entity->bundle(), $entity->getEntityTypeId()));
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
   *   The content entity to find a matching group audience field for.
   * @param string $group_type
   *   The group type that should be referenced by the group audience field.
   * @param string $group_bundle
   *   The group bundle that should be referenced by the group audience field.
   * @param bool $check_access
   *   Set this to FALSE to not check if the current user has access to the
   *   field. Defaults to TRUE.
   *
   * @return string
   *   The name of the group audience field.
   */
  public static function getMatchingField(ContentEntityInterface $entity, $group_type, $group_bundle, $check_access = TRUE) {
    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();

    $field_names = Og::getAllGroupAudienceFields($entity_type_id, $bundle_id);
    if (!$field_names) {
      return;
    }
    foreach ($field_names as $field_name => $label) {
      $field = field_info_field($field_name);
      $settings = $field['settings'];
      if ($settings['target_type'] != $group_type) {
        // Group type doesn't match.
        continue;
      }
      if (!empty($settings['handler_settings']['target_bundles']) && !in_array($group_bundle, $settings['handler_settings']['target_bundles'])) {
        // Bundles don't match.
        continue;
      }

      if (!og_check_field_cardinality($entity_type_id, $entity, $field_name)) {
        // Field reached maximum.
        continue;
      }

      if ($check_access && !field_access('view', $field, $entity_type_id, $entity)) {
        // User can't access field.
        continue;
      }

      return $field_name;
    }
  }

}
