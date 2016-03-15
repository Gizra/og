<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Class OgMembershipReferenceItem.
 *
 * @FieldType(
 *   id = "og_membership_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference for non user based entity."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "og_complex",
 *   default_formatter = "og_complex",
 *   list_class = "\Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItemList",
 *   constraints = {"ValidOgMembershipReference" = {}}
 * )
 */
class OgMembershipReferenceItem extends OgStandardReferenceItem {

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
