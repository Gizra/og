<?php

namespace Drupal\og_test\Plugin\OgFields;

use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;

/**
 * A test field that can be attached only to the node entity.
 *
 * @OgFields(
 *  id = "entity_restricted",
 *  type = "group",
 *  description = @Translation("A test field that can be attached only to the node entity.")
 * )
 */
class EntityRestrictedField extends OgFieldBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageConfigBaseDefinition(array $values = array()) {
    $values = [
      // Restrict the allowed entities.
      'entity' => ['node'],
      'type' => 'list_integer',
    ];

    return parent::getFieldStorageConfigBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldConfigBaseDefinition(array $values = array()) {
    return parent::getFieldConfigBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDefinition() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition()  {
    return [];
  }
}
