<?php

/**
 * @file
 * Contains \Drupal\og\OgGroupAudienceHelper.
 */

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
   *   The content entity to find a matching group audience field for.
   * @param string $group_type
   *   The group type that should be referenced by the group audience field.
   * @param string $group_bundle
   *   The group bundle that should be referenced by the group audience field.
   * @param bool $check_access
   *   Set this to FALSE to not check if the current user has access to the
   *   field. Defaults to TRUE.
   *
   * @return string|NULL
   *   The name of the group audience field, or NULL if no matching field was
   *   found.
   */
  public static function getMatchingField(ContentEntityInterface $entity, $group_type, $group_bundle, $check_access = TRUE) {
    $fields = Og::getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle());

    // Bail out if there are no group audience fields.
    if (!$fields) {
      // @todo Throw an exception or return an empty string instead of returning
      //   NULL?
      return NULL;
    }

    foreach ($fields as $field_name => $field) {
      $handler_settings = $field->getSetting('handler_settings');

      if (
        // Skip if the group type doesn't match.
        $field->getSetting('target_type') !== $group_type ||

        // Skip if the bundle doesn't match.
        (!empty($handler_settings['target_bundles']) && !in_array($group_bundle, $handler_settings['target_bundles'])) ||

        // Skip if the field reached maximum.
        !static::checkFieldCardinality($entity, $field_name) ||

        // Skip if the user doesn't have access to the field.
        ($check_access && !$entity->$field_name->access('view'))
      ) {
        continue;
      }

      return $field_name;
    }

    return NULL;
  }

}
