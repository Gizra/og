<?php
/**
 * Base class for OG field plugin.
 */

namespace Drupal\og;

use Drupal\Core\Plugin\PluginBase;

abstract class OgFieldBase extends PluginBase implements OgFieldsInterface {

  /**
   * @var String
   *
   * The entity bundle.
   */
  protected $bundle;

  /**
   * @var String
   *
   * The entity type.
   */
  protected $entityType;

  /**
   * @var String
   *
   * The field name if often the same as the field identifier, however it is
   * overridable. For example, the group audience field is identified as
   * OG_AUDIENCE_FIELD, however the actual field name attached to the bundle can
   * be arbitrary.
   */
  protected $fieldName;

  /**
   * @param String $bundle
   *   The entity bundle.
   *
   * @return OgFieldBase
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;

    return $this;
  }

  /**
   * @return String
   *   The entity bundle.
   */
  public function getBundle() {
    return $this->bundle;
  }


  /**
   * @return String
   *   The entity type name.
   */
  public function getEntityType() {
    return $this->entityType;
  }

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
  public function setEntityType($entity_type) {
    $field_storage = $this->getFieldStorageConfigBaseDefinition();

    if (!empty($field_storage['entity']) && !in_array($entity_type, $field_storage['entity'])) {
      // @todo: We need to make sure the field name is being set before the
      // entity for this to work properly, which is error prone.
      $field_name = $this->getFieldName();
      throw new \Exception(sprintf('The field %s can not be attached to %s entity type as the field allows attachment only to certain types.', $field_name, $entity_type));
    }

    $this->entityType = $entity_type;

    return $this;
  }

  /**
   * @return String
   *   The field name.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Set the field name.
   *
   * @param String $fieldName
   *   The field name.
   *
   * @return \Drupal\og\OgFieldBase
   */
  public function setFieldName($fieldName) {
    $this->fieldName = $fieldName;

    return $this;
  }

}
