<?php

namespace Drupal\og;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

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
