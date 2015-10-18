<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
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
  public function fieldDefinition(array $field = []) {
    $definition = [
      'field_name' => OG_DEFAULT_ACCESS_FIELD,
      'entity_type' => $this->getEntityType(),
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          0 => 'Use default roles and permissions',
          1 => 'Override default roles and permissions',
        ],
        'allowed_values_function' => '',
      ],
    ] + $field;

    return FieldStorageConfig::create($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition(array $instance = []) {
    $definition = [
      'label' => t('Group roles and permissions'),
      'description' => t('Determine if group should use default roles and permissions.'),
      'default_value' => [0 => ['value' => 0]],
      'display_label' => 1,
      'required' => TRUE,
      'field_name' => OG_DEFAULT_ACCESS_FIELD,
      'entity_type' => $this->getEntityType(),
      'bundle' => $this->getBundle(),
    ] + $instance;

    return FieldConfig::create($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDefinition(array $widget = []) {
    return [
      'type' => 'options_select',
      'settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition(array $view_mode = [])  {
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
