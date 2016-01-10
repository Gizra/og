<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Class OgMembershipReferenceItem.
 *
 * @FieldType(
 *   id = "og_standard_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference for user based entity."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "og_complex",
 *   default_formatter = "og_complex",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidReference" = {}, "ValidOgMembershipReference" = {}}
 * )
 */
class OgStandardReferenceItem extends OgMembershipReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    // @todo When the FieldStorageConfig::hasCustomStorage method can be changed
    // this will not be needed to prevent errors. Can just be an empty array,
    // similar to PathItem.
    return ['columns' => []];
  }

}
