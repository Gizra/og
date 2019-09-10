<?php

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Class OgStandardReferenceItem.
 *
 * @FieldType(
 *   id = "og_standard_reference",
 *   label = @Translation("OG reference"),
 *   description = @Translation("An entity reference field containing an OG reference for a non-user entity."),
 *   category = @Translation("Reference"),
 *   no_ui = TRUE,
 *   default_widget = "options_select",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   constraints = {"ValidOgMembershipReference" = {}}
 * )
 */
class OgStandardReferenceItem extends EntityReferenceItem {

}
