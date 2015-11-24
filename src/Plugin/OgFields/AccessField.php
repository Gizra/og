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
  public function fieldDefinition() {
    return [
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    return [
      'label' => $this->t('Group roles and permissions'),
      'description' => $this->t('Determine if group should use default roles and permissions.'),
      'default_value' => [0 => ['value' => 0]],
      'display_label' => 1,
      'required' => TRUE,
      'field_name' => OG_DEFAULT_ACCESS_FIELD,
      'entity_type' => $this->getEntityType(),
      'bundle' => $this->getBundle(),
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
