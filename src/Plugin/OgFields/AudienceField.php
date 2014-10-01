<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;

/**
 * Redirects to a message deletion form.
 *
 * @OgFields(
 *  id = OG_AUDIENCE_FIELD,
 *  type = "group",
 *  description = @Translation("Determine to which groups this group content is assigned to."),
 * )
 */
class AudienceField extends OgFieldBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function fieldDefinition() {
    return FieldStorageConfig::create(array(
      'field_name' => OG_AUDIENCE_FIELD,
      'entity_type' => $this->getEntityType(),
      'type' => 'entity_reference',
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
        // todo: allow to change the node type.
        'target_type' => 'node',
      ),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function instanceDefinition() {
    return FieldConfig::create(array(
      'label' => t('Groups audience'),
      'description' => t('Determine if this is an OG group.'),
      'default_value' => array(0 => array('value' => 1)),
      'display_label' => 1,
      'field_name' => OG_AUDIENCE_FIELD,
      'entity_type' => $this->getEntityType(),
      'bundle' => $this->getBundle(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function widgetDefinition() {
    // Keep this until og_complex widget is back.
    return array(
      'type' => "og_complex",
      'settings' => array(
        'match_operator' => "CONTAINS"
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition() {
    return array(
      'default' => array(
        'label' => "above",
        'type' => "entity_reference_label",
        'settings' => array(
          'link' => TRUE,
        ),
      ),
      'teaser' => array(
        'label' => "above",
        'type' => "entity_reference_label",
        'settings' => array(
          'link' => TRUE,
        ),
      ),
    );
  }
}
