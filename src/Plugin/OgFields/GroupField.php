<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Plugin\PluginBase;
use Drupal\field\Entity\FieldInstanceConfig;
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
    return FieldDefinition::create('list_integer')
      ->setName(OG_GROUP_FIELD)
      ->setCardinality(1)
      ->setSetting('allowed_values', array(0 => 'Not a group', 1 => 'Group'));
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
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDefinition() {
    $config = array(
      'module' => 'options',
      'settings' => array(
        'og_hide' => TRUE,
      ),
      'type' => 'options_onoff',
      'weight' => 0,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition() {
    $config = array(
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
    );
  }

}
