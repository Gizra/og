<?php

namespace Drupal\og\Plugin\OgFields;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\og\OgFieldBase;
use Drupal\og\OgFieldsInterface;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Determine to which groups this group content is assigned to.
 *
 * @OgFields(
 *  id = "og_audience",
 *  type = "group",
 *  description = @Translation("Determine to which groups this group content is assigned to."),
 * )
 */
class AudienceField extends OgFieldBase implements OgFieldsInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageBaseDefinition(array $values = []) {
    if ($this->getEntityType() === 'user') {
      throw new \LogicException('OG audience field cannot be added to the User entity type.');
    }

    $values += [
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => $this->getEntityType(),
      ],
      'type' => OgGroupAudienceHelperInterface::GROUP_REFERENCE,
    ];

    return parent::getFieldStorageBaseDefinition($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldBaseDefinition(array $values = []) {
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
        'match_limit' => 10,
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
      ],
    ];

    return $values;
  }

}
