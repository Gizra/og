<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldWidget\OgComplex.
 */

namespace Drupal\og\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "og_complex",
 *   label = @Translation("OG reference"),
 *   description = @Translation("An autocompletewidget for OG"),
 *   field_types = {
 *     "og_membership_reference"
 *   }
 * )
 */
class OgComplex extends EntityReferenceAutocompleteWidget {

  /**
   * The OG complex widget have a special logic on order to return the groups
   * that user can reference to.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $return = parent::formElement($items, $delta, $element, $form, $form_state);

    // todo remove this after the selection handler could be changed via th UI.
    $return['target_id']['#selection_handler'] = 'default:node';

    return $return;
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

    $entity = \Drupal::entityManager()->getStorage($this->getFieldSetting('target_type'))->load($matches[1]);

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
