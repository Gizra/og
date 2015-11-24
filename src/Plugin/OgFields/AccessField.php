<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;

/**
 * Redirects to a message deletion form.
 *
 * @OgFields(
 *  id = OG_DEFAULT_ACCESS_FIELD,
 *  type = "group",
 *  description = @Translation("Determine if group should use default roles and permissions.")
 * )
 */
class AccessField extends OgFieldBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageConfigBaseDefinition() {
    return [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'entity_type' => $this->getEntityType(),
      'field_name' => $this->getFieldName(),
      'settings' => [
        'allowed_values' => [
          0 => 'Use default roles and permissions',
          1 => 'Override default roles and permissions',
        ],
        'allowed_values_function' => '',
      ],
      'type' => 'list_integer',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldConfigBaseDefinition() {
    return [
      'bundle' => $this->getBundle(),
      'default_value' => [0 => ['value' => 0]],
      'description' => $this->t('Determine if group should use default roles and permissions.'),
      'display_label' => TRUE,
      'entity_type' => $this->getEntityType(),
      'field_name' => $this->getFieldName(),
      'label' => $this->t('Group roles and permissions'),
      'required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDefinition() {
    return [
      'type' => 'options_select',
      'settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition()  {
    return [
      'default' => [
        'type' => 'list_default',
        'label' => 'above',
      ],
      'teaser' => [
        'type' => 'list_default',
        'label' => 'above',
      ],
    ];
  }
}
