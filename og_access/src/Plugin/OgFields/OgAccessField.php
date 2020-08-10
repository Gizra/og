<?php

declare(strict_types = 1);

namespace Drupal\og_access\Plugin\OgFields;

use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;

/**
 * Determine if group should use default roles and permissions.
 *
 * @OgFields(
 *  id = \Drupal\og_access\OgAccess::OG_ACCESS_FIELD,
 *  type = "group",
 *  description = @Translation("Determine access to the group.")
 * )
 */
class OgAccessField extends OgFieldBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageBaseDefinition(array $values = []) {
    $values += [
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          0 => $this->t('Public - accessible to all site users'),
          1 => $this->t('Private - accessible only to group members'),
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
    $values += [
      'default_value' => [0 => ['value' => 0]],
      'description' => $this->t('Determine access to the group.'),
      'display_label' => TRUE,
      'label' => $this->t('Group visibility'),
      'required' => TRUE,
    ];

    return parent::getFieldBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplayDefinition(array $values = []) {
    $values += [
      'type' => 'options_buttons',
      'settings' => [],
      'default_value' => 0,
    ];

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewDisplayDefinition(array $values = []) {
    $values += [
      'type' => 'list_default',
      'label' => 'above',
    ];

    return $values;
  }

}
