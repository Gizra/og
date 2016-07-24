<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldException;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * OG audience field helper methods.
 */
class OgGroupAudienceHelper {

  /**
   * The default OG audience field name.
   */
  const DEFAULT_FIELD = 'og_audience';

  /**
   * The name of the field type that references non-user entities to groups.
   */
  const NON_USER_TO_GROUP_REFERENCE_FIELD_TYPE = 'og_standard_reference';

  /**
   * Return TRUE if field is a group audience type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition object.
   *
   * @return bool
   *   TRUE if the field is a group audience type, FALSE otherwise.
   */
  public static function isGroupAudienceField(FieldDefinitionInterface $field_definition) {
    return $field_definition->getType() == OgGroupAudienceHelper::NON_USER_TO_GROUP_REFERENCE_FIELD_TYPE;
  }

  /**
   * Determine if a field can be used and has not reached maximum values.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to check the field cardinality for.
   * @param string $field_name
   *   The field name to check the cardinality of.
   *
   * @return bool
   *   Returns TRUE if the OG audience field can be used.
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

    if (!static::isGroupAudienceField($field_definition)) {
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
   * @return string|null
   *   The name of the group audience field, or NULL if no matching field was
   *   found.
   */
  public static function getMatchingField(ContentEntityInterface $entity, $group_type, $group_bundle, $check_access = TRUE) {
    $fields = static::getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle());

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
        // The field cardinality has reached its maximum.
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
   * Returns all the group audience fields of a certain bundle.
   *
   * @param string $group_content_entity_type_id
   *   The entity type ID of the group content for which to return audience
   *   fields.
   * @param string $group_content_bundle_id
   *   The bundle name of the group content for which to return audience fields.
   * @param string $group_entity_type_id
   *   Filter list to only include fields referencing a specific group type. If
   *   omitted, all fields will be returned.
   * @param string $group_bundle_id
   *   Filter list to only include fields referencing a specific group bundle.
   *   Fields that do not specify any bundle restrictions at all are also
   *   included. If omitted, the results will not be filtered by group bundle.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name; Or an empty array if
   *   none found.
   */
  public static function getAllGroupAudienceFields($group_content_entity_type_id, $group_content_bundle_id, $group_entity_type_id = NULL, $group_bundle_id = NULL) {
    $return = [];
    $entity_type = \Drupal::entityTypeManager()->getDefinition($group_content_entity_type_id);

    if (!$entity_type->isSubclassOf(FieldableEntityInterface::class)) {
      // This entity type is not fieldable.
      return [];
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions */
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($group_content_entity_type_id, $group_content_bundle_id);

    foreach ($field_definitions as $field_definition) {
      if (!static::isGroupAudienceField($field_definition)) {
        // Not a group audience field.
        continue;
      }

      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');

      if (isset($group_entity_type_id) && $target_type != $group_entity_type_id) {
        // Field doesn't reference this group type.
        continue;
      }

      $handler_settings = $field_definition->getSetting('handler_settings');

      if (isset($group_bundle_id) && !empty($handler_settings['target_bundles']) && !in_array($group_bundle_id, $handler_settings['target_bundles'])) {
        continue;
      }

      $field_name = $field_definition->getName();
      $return[$field_name] = $field_definition;
    }

    return $return;
  }

}
