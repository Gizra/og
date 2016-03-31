<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\OgFields\AccessField.
 */

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
  public function getFieldStorageBaseDefinition(array $values = []) {
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

    return parent::getFieldStorageBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldBaseDefinition(array $values = []) {
    $values = [
      'default_value' => [0 => ['value' => 0]],
      'description' => $this->t('Determine if group should use default roles and permissions.'),
      'display_label' => TRUE,
      'label' => $this->t('Group roles and permissions'),
      'required' => TRUE,
    ];

    return parent::getFieldBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetDefinition(array $values = []) {
    $values['type'] = 'options_select';
    $values['settings'] = [];

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModesDefinition(array $values = [])  {
    $values['default'] = [
      'type' => 'list_default',
      'label' => 'above',
    ];

    $values['teaser'] = [
      'type' => 'list_default',
      'label' => 'above',
    ];


    return $values;
  }
}
