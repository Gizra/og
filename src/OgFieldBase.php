<?php
/**
 * Base class for OG field plugin.
 */

namespace Drupal\og;

use Drupal\Component\Render\FormattableMarkup;
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

      $params = [
        '@plugin' => $this->getPluginId(),
        '@entity' => implode(', ', $field_storage['entity']),
      ];

      if ($field_name = $this->getFieldName()) {
        $params['@field_name'] = $field_name;
        throw new \Exception(new FormattableMarkup('The Organic Groups field with plugin ID @plugin with the name @field_name cannot be attached to the entity type. It can only be attached to the following entities: @entity.', $params));
      }

      throw new \Exception(new FormattableMarkup('The Organic Groups field with plugin ID @plugin cannot be attached to the entity type. It can only be attached to the following entities: @entity.', $params));

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

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageConfigBaseDefinition(array $values = array()) {
    $values += [
      'entity_type' => $this->getEntityType(),
      'field_name' => $this->getFieldName(),
    ];

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldConfigBaseDefinition(array $values = array()) {
    $values += [
      'bundle' => $this->getBundle(),
      'entity_type' => $this->getEntityType(),
      'field_name' => $this->getFieldName(),
    ];

    return $values;
  }

}
