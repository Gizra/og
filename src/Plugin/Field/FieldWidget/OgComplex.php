<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldWidget\OgComplex.
 */

namespace Drupal\og\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_reference\Plugin\Field\FieldWidget\AutocompleteWidget;

/**
 * Plugin implementation of the 'entity_reference autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "og_complex",
 *   label = @Translation("OG reference"),
 *   description = @Translation("An autocomplete text for OG"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class OgComplex extends AutocompleteWidget {

  /**
   * The OG complex widget have a special logic on order to return the groups
   * that user can reference to.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $return = parent::formElement($items, $delta, $element, $form, $form_state);

    //$return['target_id']['#autocomplete_route_name'] = 'og.entity_reference.autocomplete';

    return $return;
  }
}
