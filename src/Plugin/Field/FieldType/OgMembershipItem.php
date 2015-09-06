<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldType\OgMembershipItem.
 */

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\og\Controller\OG;

/**
 * Class OgMembershipItem.
 *
 * @FieldType(
 *   id = "og_membership_reference",
 *   label = @Translation("OG membership reference"),
 *   description = @Translation("An entity field containing an OG membership reference."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_formatter = "og_complex",
 *   list_class = "\Drupal\og\Plugin\Field\FieldType\OgMembershipItemList",
 *   constraints = {"ValidReference" = {}}
 * )
 */
class OgMembershipItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

}
