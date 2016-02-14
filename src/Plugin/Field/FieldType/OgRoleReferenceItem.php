<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OgRoleReferenceItem.
 *
 * @FieldType(
 *   id = "og_role_reference",
 *   label = @Translation("OG role reference"),
 *   description = @Translation("Referencing OG membership to OG group roles."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "entity_reference_autocomplete",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidReference" = {}}
 * )
 */
class OgRoleReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'target_id' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ];

    return $schema;
  }

}
