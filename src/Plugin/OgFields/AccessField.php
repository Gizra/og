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
  public function fieldDefinition() {
    return FieldStorageConfig::create(array(
      'field_name' => OG_DEFAULT_ACCESS_FIELD,
      'entity_type' => $this->getEntityType(),
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(
          0 => 'Use default roles and permissions',
          1 => 'Override default roles and permissions',
        ),
        'allowed_values_function' => '',
      ),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    return FieldConfig::create(array(
      'label' => t('Group roles and permissions'),
      'description' => t('Determine if group should use default roles and permissions.'),
      'default_value' => array(0 => array('value' => 0)),
      'display_label' => 1,
      'required' => TRUE,
      'field_name' => OG_DEFAULT_ACCESS_FIELD,
      'entity_type' => $this->getEntityType(),
      'bundle' => $this->getBundle(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDefinition() {
    return array(
      'type' => 'options_select',
      'settings' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition()  {
    return array(
      'default' => array(
        'type' => "list_default",
        'label' => "above",
      ),
      'teaser' => array(
        'type' => "list_default",
        'label' => "above",
      ),
    );
  }
}
