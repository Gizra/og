<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\OgFieldBase;

/**
 * Redirects to a message deletion form.
 *
 * @OgFields(
 *  id = OG_GROUP_FIELD,
 *  type = "group",
 *  description = @Translation("Determine if this should be a group.")
 * )
 */
class FieldGroup extends OgFieldBase {

  /**
   * {@inheritdoc}
   */
  public function fieldDefinition() {
    return FieldStorageConfig::create(array(
      'field_name' => OG_GROUP_FIELD,
      'entity_type' => $this->getEntityType(),
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(0 => 'Not a group', 1 => 'Group'),
        'default_value' => 0,
      ),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    return FieldConfig::create(array(
      'label' => t('Group'),
      'description' => t('Determine if this is an OG group.'),
      'default_value' => array(0 => array('value' => 1)),
      'display_label' => 1,
      'field_name' => OG_GROUP_FIELD,
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
  public function viewModesDefinition() {
    return array(
      'default' => array(
        'type' => 'list_key',
        'label' => 'inline',
      ),
      'teaser' => array(
        'type' => 'list_key',
        'label' => 'inline',
      ),
    );
  }
}
