<?php

namespace Drupal\og;

/**
 * The OG fields related interface.
 */
interface OgFieldsInterface {

  /**
   * Set the bundle.
   *
   * @param string $bundle
   *   The entity bundle.
   *
   * @return $this
   */
  public function setBundle($bundle);

  /**
   * Get the bundle name.
   *
   * @return string
   *   The entity bundle.
   */
  public function getBundle();

  /**
   * Get the entity type name.
   *
   * @return string
   *   The entity type name.
   */
  public function getEntityType();

  /**
   * Set the entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return $this
   *
   * @throws \Exception
   *   Throw error if the field storage config definition explicitly defines to
   *   which entities the field can be attached to.
   */
  public function setEntityType($entity_type);

  /**
   * Get the field name.
   *
   * @return string
   *   The field name.
   */
  public function getFieldName();

  /**
   * Set the field name.
   *
   * The field name is often the same as the plugin ID, however it is
   * overridable. For example, the group audience field is identified as
   * \Drupal\og\OgGroupAudienceHelper::DEFAULT_FIELD, however the actual field
   * name attached to the bundle can be arbitrary.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return $this
   */
  public function setFieldName($field_name);

  /**
   * Get the field storage config base definition.
   *
   * @param array $values
   *   Values to override the base definitions.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldStorageConfig::create().
   */
  public function getFieldStorageBaseDefinition(array $values = []);

  /**
   * Get the field config base definition.
   *
   * @param array $values
   *   Values to override the base definitions.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldConfig::create().
   */
  public function getFieldBaseDefinition(array $values = []);

  /**
   * Get the field's form display definition.
   *
   * @param array $values
   *   Values to override the base definitions.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldConfig::create().
   */
  public function getFormDisplayDefinition(array $values = []);

  /**
   * Get the field's view modes definition.
   *
   * @param array $values
   *   Values to override the base definitions.
   *
   * @return array
   *   Array that will be used as the base values for
   *   FieldConfig::create().
   */
  public function getViewDisplayDefinition(array $values = []);

}
