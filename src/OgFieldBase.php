<?php

/**
 * @file
 * Contains \Drupal\og\OgFieldBase.
 */

namespace Drupal\og;

use Drupal\Core\Plugin\PluginBase;

abstract class OgFieldBase extends PluginBase implements OgFieldsInterface {

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The field name.
   *
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }


  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($entity_type) {
    $field_storage = $this->getFieldStorageConfigBaseDefinition();

    if (!empty($field_storage['entity']) && !in_array($entity_type, $field_storage['entity'])) {


      $plugin_id = $this->getPluginId();
      $entities = implode(', ', $field_storage['entity']);

      if ($field_name = $this->getFieldName()) {
        $params['@field_name'] = $field_name;
        throw new \Exception(sprintf('The Organic Groups field with plugin ID %s with the name %s cannot be attached to the entity type. It can only be attached to the following entities: %s.', $plugin_id, $field_name, $entities));
      }

      throw new \Exception(sprintf('The Organic Groups field with plugin ID %s cannot be attached to the entity type. It can only be attached to the following entities: %s.', $plugin_id, $entities));

    }

    $this->entityType = $entity_type;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * {@inheritdoc}
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
