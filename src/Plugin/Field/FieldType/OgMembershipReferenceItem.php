<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\og\Controller\OG;

/**
 * Class OgMembershipReferenceItem.
 *
 * @FieldType(
 *   id = "og_membership_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_formatter = "og_complex",
 *   list_class = "\Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItemList",
 *   constraints = {"ValidReference" = {}, "ValidOgMembershipReference" = {}}
 * )
 */
class OgMembershipReferenceItem extends EntityReferenceItem {

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
