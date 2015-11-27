<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;

/**
 * Determine if group should use default roles and permissions.
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
  public function getFieldStorageConfigBaseDefinition(array $values = array()) {
    $values = [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'allowed_values' => [
          0 => 'Use default roles and permissions',
          1 => 'Override default roles and permissions',
        ],
        'allowed_values_function' => '',
      ],
      'type' => 'list_integer',
    ];

    return parent::getFieldStorageConfigBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldConfigBaseDefinition(array $values = array()) {
    $values = [
      'default_value' => [0 => ['value' => 0]],
      'description' => $this->t('Determine if group should use default roles and permissions.'),
      'display_label' => TRUE,
      'label' => $this->t('Group roles and permissions'),
      'required' => TRUE,
    ];

    return parent::getFieldConfigBaseDefinition($values);
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
