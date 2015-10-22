<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Field\FieldWidget\OgComplex.
 */

namespace Drupal\og\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Controller\OG;

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
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $parent = parent::formElement($items, $delta, $element, $form, $form_state);
    // todo: fix the definition in th UI level.
    $parent['target_id']['#selection_handler'] = 'default:og';
    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $parent_form = parent::form($items, $form, $form_state, $get_delta);

    $parent_form['other_groups'] = [];

    // Adding the other groups widget.
    $this->otherGroupsWidget($parent_form['other_groups'], $form_state);
    return $parent_form;
  }

  /**
   * Adding the other groups widget to the form.
   *
   * @param $elements
   *   The widget array.
   */
  protected function otherGroupsWidget(&$elements, FormStateInterface $form_state) {
    if (!\Drupal::currentUser()->hasPermission('administer groups')) {
      return;
    }

    if ($this->fieldDefinition->getTargetEntityTypeId() == 'user') {
      $description = $this->t('As groups administrator, associate this user with groups you do <em>not</em> belong to.');
    }
    else {
      $description = $this->t('As groups administrator, associate this content with groups you do <em>not</em> belong to.');
    }

    $field_wrapper = Html::getClass($this->fieldDefinition->getName()) . '-add-another-group';

    $elements = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title' => $this->t('Other groups'),
      '#description' => $description,
      '#prefix' => '<div id="' . $field_wrapper . '">',
      '#suffix' => '</div>',
      '#cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      '#cardinality_multiple' => TRUE,
      '#theme' => 'field_multiple_value_form',
      '#field_name' => $this->fieldDefinition->getName(),
      '#max_delta' => 1,
    ];

    $elements['add_more'] = [
      '#type' => 'button',
      '#value' => $this->t('Add another item'),
      '#name' => 'add_another_group',
      '#ajax' => [
        'callback' => [$this, 'addMoreAjax'],
        'wrapper' => $field_wrapper,
        'effect' => 'fade',
      ],
    ];

    $start_key = 0;
    $other_groups = OG::getEntityGroups();
    foreach ($other_groups[$this->fieldDefinition->getTargetEntityTypeId()] as $other_group) {
      $elements[$start_key] = $this->otherGroupsSingle($start_key, $other_group);
      $start_key++;
    }

    if (!$form_state->get('other_group_delta')) {
      $form_state->set('other_group_delta', $start_key);
    }

    // Get the trigger element and check if this the add another item button.
    $trigger_element = $form_state->getTriggeringElement();

    if ( $trigger_element['#name'] == 'add_another_group') {
      // Increase the number of other groups.
      $delta = $form_state->get('other_group_delta') + 1;
      $form_state->set('other_group_delta', $delta);
    }

    // Add another auto complete field.
    for ($i = $start_key; $i <= $form_state->get('other_group_delta'); $i++) {
      $elements[$i] = $this->otherGroupsSingle($i);
    }
  }

  /**
   * Generating other groups auto complete element.
   *
   * @param $delta
   *   The delta of the new element. Need to be the last delta in order to be
   *   added in the end of the list.
   * @param EntityInterface|NULL $entity
   *   The entity object.
   * @return array
   *   A single entity reference input.
   */
  public function otherGroupsSingle($delta, EntityInterface $entity = NULL) {
    return [
      'target_id' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => $this->fieldDefinition->getTargetEntityTypeId(),
        // todo: fix the definition in th UI level.
        '#selection_handler' => 'default:og',
        '#selection_settings' => [
          'other_groups' => TRUE,
        ],
        '#default_value' => $entity,
      ],
      '_weight' => [
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#delta' => $delta,
        '#default_value' => $delta,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $parent_values = $values;

    // Get the groups from the other groups widget.
    foreach ($form[$this->fieldDefinition->getName()]['other_groups'] as $key => $value) {
      if (!is_int($key)) {
        continue;
      }

      preg_match("/.+\(([\w.]+)\)/", $value['target_id']['#value'], $matches);

      if (empty($matches[1])) {
        continue;
      }

      $parent_values[] = [
        'target_id' => $matches[1],
        '_weight' => $value['_weight']['#value'],
        '_original_delta' => $value['_weight']['#delta'],
      ];
    }

    return $parent_values;
  }

}
