<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Plugin\PluginBase;
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
class DefaultAccessField extends PluginBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function fieldDefinition() {
    $config = array(
      'field_name' => OG_DEFAULT_ACCESS_FIELD,
      'type' => 'list_boolean',
      'cardinality' => 1,
      'settings' => array('allowed_values' => array(0 => 'Use default roles and permissions', 1 => 'Override default roles and permissions'), 'allowed_values_function' => ''),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    $config = array(
      'label' => t('Group roles and permissions'),
      'widget' => array(
        'module' => 'options',
        'settings' => array(),
        'type' => 'options_select',
      ),
      'required' => TRUE,
      // Use default role and permissions as default value.
      'default_value' => array(0 => array('value' => 0)),
      'view modes' => array(
        'full' => array(
          'label' => t('Full'),
          'type' => 'list_default',
          'custom settings' => FALSE,
        ),
        'teaser' => array(
          'label' => t('Teaser'),
          'type' => 'list_default',
          'custom settings' => FALSE,
        ),
      ),
    );
  }

}