<?php

namespace Drupal\og;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Interface for a service that deals with group audience fields.
 */
interface OgGroupAudienceHelperInterface {
  /**
   * The name of the field type that references non-user entities to groups.
   */
  const GROUP_REFERENCE = 'og_standard_reference';
  /**
   * The default OG audience field name.
   */
  const DEFAULT_FIELD = 'og_audience';

  /**
   * Returns whether the given entity bundle has a group audience field.
   *
   * This can be used to determine whether the bundle is group content.
   *
   * @param string $entity_type_id
   *   The entity type ID to check for the presence of group audience fields.
   * @param string $bundle_id
   *   The bundle name to check for the presence of group audience fields.
   *
   * @return bool
   *   TRUE if the field is a group audience type, FALSE otherwise.
   */
  public function hasGroupAudienceField($entity_type_id, $bundle_id);

  /**
   * Returns TRUE if field is a group audience type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition object.
   *
   * @return bool
   *   TRUE if the field is a group audience type, FALSE otherwise.
   */
  public static function isGroupAudienceField(FieldDefinitionInterface $field_definition);

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
  public function getAllGroupAudienceFields($group_content_entity_type_id, $group_content_bundle_id, $group_entity_type_id = NULL, $group_bundle_id = NULL);

}
