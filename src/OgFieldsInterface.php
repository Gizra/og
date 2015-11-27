<?php
/**
 * Contains
 */

namespace Drupal\og;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

interface OgFieldsInterface {

  /**
   * Set the bundle.
   *
   * @param string $bundle
   *   The entity bundle.
   *
   * @return OgFieldBase
   */
  public function setBundle($bundle);

  /**
   * Get the bundle name.
   *
   * @return String
   *   The entity bundle.
   */
  public function getBundle();


  /**
   * Get the entity type name.
   *
   * @return String
   *   The entity type name.
   */
  public function getEntityType();

  /**
   * Set the entity type.
   *
   * @param String $entity_type
   *   The entity type.
   *
   * @return \Drupal\og\OgFieldBase
   * @throws \Exception
   *   Throw error if the field storage config definition explicitly defines to
   *   which entities the field can be attached to.
   */
  public function setEntityType($entity_type);

  /**
   * Get the field name.
   *
   * @return String
   *   The field name.
   */
  public function getFieldName();

  /**
   * Set the field name.
   *
   * The field name is often the same as the plugin ID, however it is
   * overridable. For example, the group audience field is identified as
   * OG_AUDIENCE_FIELD, however the actual field name attached to the bundle can
   * be arbitrary.
   *
   * @param String $fieldName
   *   The field name.
   *
   * @return \Drupal\og\OgFieldBase
   */
  public function setFieldName($fieldName);

  /**
   * Get the field storage config base definition.
   *
   * @param array $values
   *   The base values, to which the entity type and field name would be added.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldStorageConfig::create().
   */
  public function getFieldStorageConfigBaseDefinition(array $values = array());

  /**
   * Get the field config base definition.
   *
   * @param array $values
   *   The base values, to which the entity type, bundle and field name would be
   *   added.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldConfig::create().
   */
  public function getFieldConfigBaseDefinition(array $values = array());

  /**
   * @return array
   *   A widget definition for the field.
   */
  public function widgetDefinition();

  /**
   * @return
   *   Return view modes entities for the field.
   */
  public function viewModesDefinition();
}
