<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Plugin\PluginBase;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;

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
      'name' => OG_GROUP_FIELD,
      'entity_type' => $this->getEntityType(),
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(0 => 'Not a group', 1 => 'Group'),
      ),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    return FieldInstanceConfig::create(array(
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
      'third_party_settings' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition() {
    $prefix = $this->getEntityType() . '.' . $this->getBundle() . '.';

    return array(
      $prefix . 'default' => array(
        'type' => 'list_key',
        'label' => 'inline',
      ),
      $prefix . 'teaser' => array(
        'type' => 'list_key',
        'label' => 'inline',
      ),
    );
  }
}
