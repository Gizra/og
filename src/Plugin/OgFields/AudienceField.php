<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\OgFields\AudienceField.
 */

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;

/**
 * Determine to which groups this group content is assigned to.
 *
 * @OgFields(
 *  id = "og_group_ref",
 *  type = "group",
 *  description = @Translation("Determine to which groups this group content is assigned to."),
 * )
 */
class AudienceField extends OgFieldBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageBaseDefinition(array $values = array()) {
    $values += [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'custom_storage' => $this->getEntityType() == 'user',
      'settings' => [
        'target_type' => $this->getEntityType(),
      ],
      'type' => $this->getEntityType() == 'user' ? 'og_membership_reference' : 'og_standard_reference',
    ];

    return parent::getFieldStorageBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldBaseDefinition(array $values = array()) {
    $values += [
      'description' => $this->t('OG group audience reference field.'),
      'display_label' => TRUE,
      'label' => $this->t('Groups audience'),
      'settings' => [
        'handler' => 'og',
        'handler_settings' => [],
      ],
    ];

    return parent::getFieldBaseDefinition($values);

  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplayDefinition(array $values = []) {
    $values += [
      'type' => 'og_complex',
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'placeholder' => '',
      ],
    ];


    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewDisplayDefinition(array $values = []) {
    $values += [
      'label' => 'above',
      'type' => 'entity_reference_label',
      'settings' => [
        'link' => TRUE,
      ]
    ];

    return $values;
  }
}
