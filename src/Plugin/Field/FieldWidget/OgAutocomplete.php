<?php

declare(strict_types=1);

namespace Drupal\og\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'og_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "og_autocomplete",
 *   label = @Translation("OG context autocomplete"),
 *   description = @Translation("An autocomplete widget that takes OG group context into account"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class OgAutocomplete extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $entity = $items->getEntity();
    if (!is_callable([$entity, 'getGroup'])) {
      return $element;
    }

    $element['target_id']['#type'] = 'og_autocomplete';
    $element['target_id']['#og_group'] = $entity->getGroup();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element;
  }

}
