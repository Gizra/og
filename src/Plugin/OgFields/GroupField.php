<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Plugin\PluginBase;
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
class GroupField extends PluginBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function fieldDefinition() {
    $config = array(
      'field_name' => OG_GROUP_FIELD,
      'type' => 'list_boolean',
      'cardinality' => 1,
      'settings' => array(
        'allowed_values' => array(0 => 'Not a group', 1 => 'Group'),
        'allowed_values_function' => '',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    $config = array(
      'label' => t('Group'),
      'description' => t('Determine if this is an OG group.'),
      'display_label' => 1,
      'widget' => array(
        'module' => 'options',
        'settings' => array(
          'og_hide' => TRUE,
        ),
        'type' => 'options_onoff',
        'weight' => 0,
      ),
      'default_value' => array(0 => array('value' => 1)),
      'view modes' => array(
        'full' => array(
          'label' => t('Full'),
          'type' => 'og_group_subscribe',
          'custom settings' => FALSE,
        ),
        'teaser' => array(
          'label' => t('Teaser'),
          'type' => 'og_group_subscribe',
          'custom settings' => FALSE,
        ),
      ),
    );
  }

}