<?php

/**
 * @file
 * Contains \Drupal\og\OgGroupItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * OG Group Data class to return TRUE always.
 *
 * @FieldType(
 *   id = "og_group",
 *   label = @Translation("OG Group"),
 *   description = @Translation("OG Group"),
 *   category = @Translation("OG"),
 *   no_ui = TRUE,
 *   default_formatter = "og_ui_group_subscribe",
 * )
 */
class OgGroupItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $definitions = [];

    $definitions['value'] = DataDefinition::create('boolean')
      ->setLabel('Group item value')
      ->setReadOnly(TRUE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\og\OgGroupBoolean')
      ->setSetting('group_value', TRUE);

    return $definitions;
  }

}
