<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;

/**
 * Determine to which groups this group content is assigned to.
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
  public function getFieldStorageConfigBaseDefinition(array $values = array()) {
    $values = [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'custom_storage' => TRUE,
      'settings' => [
        'handler' => 'og',
        'handler_settings' => [
          'target_bundles' => [],
          'membership_type' => OG_MEMBERSHIP_TYPE_DEFAULT,
        ],
        'target_type' => $this->getEntityType(),
      ],
      'type' => 'og_membership_reference',
    ];

    return parent::getFieldStorageConfigBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldConfigBaseDefinition(array $values = array()) {
    $values = [
      'description' => $this->t('OG group audience reference field.'),
      'display_label' => TRUE,
      'label' => $this->t('Groups audience'),
    ];

    return parent::getFieldConfigBaseDefinition($values);

  }

  /**
   * {@inheritdoc}
   */
  public function widgetDefinition(array $widget = []) {
    // Keep this until og_complex widget is back.
    return [
      'type' => 'og_complex',
      'settings' => [
        'match_operator' => 'CONTAINS',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewModesDefinition(array $view_mode = []) {
    return [
      'default' => [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => TRUE,
        ]
      ],
      'teaser' => [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => TRUE,
        ],
      ],
    ];
  }
}
