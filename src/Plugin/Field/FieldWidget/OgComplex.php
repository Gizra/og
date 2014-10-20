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

    $return['target_id']['#autocomplete_route_name'] = 'og.entity_reference.autocomplete';

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    // todo: issue #2 in OG 8 issue queue.
    $elements = parent::formMultipleElements($items, $form, $form_state);

    return $elements;
  }

  /**
   * Override the parent method. Additional to the entity reference validation
   * there is another validation: check if the given entities are groups.
   *
   * A user can change the ID in the brackets easily and reference the group
   * content to a non-group entity.
   */
  public function elementValidate($element, FormStateInterface $form_state, $form) {
    parent::elementValidate($element, $form_state, $form);

    preg_match("/.+\(([\w.]+)\)/", $element['#value'], $matches);

    if (!$matches[1]) {
      return;
    }

    $entity = entity_load($this->getFieldSetting('target_type'), $matches[1]);

    $params['%label'] = $entity->label();

    if (!$entity->hasField(OG_GROUP_FIELD)) {
      $form_state->setError($element, t('The entity %label is not defined as a group.', $params));
      return;
    }

    if (!$entity->get(OG_GROUP_FIELD)->value) {
      $form_state->setError($element, t('The entity %label is not a group.', $params));
      return;
    }

    // todo: Check the writing permission for the current user.
  }
}
