<?php

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * OG Group Data class to return TRUE always.
 *
 * This field type is used simply as a placeholder for a field formatter that
 * would allow non-groups members, members, and group managers to join, request
 * or leave a group.
 *
 * @FieldType(
 *   id = "og_group",
 *   label = @Translation("OG Group"),
 *   description = @Translation("allow non-groups members, members, and group managers to join, request or leave a group"),
 *   category = @Translation("OG"),
 *   no_ui = TRUE,
 *   default_formatter = "og_group_subscribe",
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
      ->setClass('\Drupal\og\OgGroupAlwaysTrue');

    return $definitions;
  }

}
