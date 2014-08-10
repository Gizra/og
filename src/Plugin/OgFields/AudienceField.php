<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\og\OgFieldsInterface;

/**
 * Redirects to a message deletion form.
 *
 * @OgFields(
 *  id = OG_AUDIENCE_FIELD,
 *  type = "group",
 *  description = @Translation("Determine to which groups this group content is assigned to."),
 *  multiple = TRUE
 * )
 */
class AudienceField extends PluginBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function fieldDefinition() {
    $config = array(
      'field_name' => OG_AUDIENCE_FIELD,
      'type' => 'entityreference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'handler' => 'og',
        'handler_submit' => 'Change handler',
        'handler_settings' => array(
          'behaviors' => array(
            'og_behavior' => array(
              'status' => TRUE,
            ),
          ),
          'target_bundles' => array(),
          'membership_type' => OG_MEMBERSHIP_TYPE_DEFAULT,
        ),
        'target_type' => 'node',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    $config = array(
      'label' => t('Groups audience'),
      'widget' => array(
        'type' => 'og_complex',
        'module' => 'og',
        'settings' => array(),
      ),
      'settings' => array(
        'behaviors' => array(
          'og_widget' => array(
            'status' => TRUE,
            'default' => array(
              'widget_type' => 'options_select',
            ),
            'admin' => array(
              'widget_type' => 'entityreference_autocomplete',
            ),
          ),
        ),
      ),
      'view modes' => array(
        'full' => array(
          'label' => t('Full'),
          'type' => 'og_list_default',
          'custom settings' => FALSE,
        ),
        'teaser' => array(
          'label' => t('Teaser'),
          'type' => 'og_list_default',
          'custom settings' => FALSE,
        ),
      ),
    );
  }

}