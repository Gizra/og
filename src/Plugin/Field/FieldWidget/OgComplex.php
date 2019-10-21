<?php

namespace Drupal\og\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgAccess;

/**
 * Plugin implementation of the 'entity_reference autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "og_complex",
 *   label = @Translation("OG reference"),
 *   description = @Translation("An autocompletewidget for OG"),
 *   field_types = {
 *     "og_standard_reference",
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
    $parent['target_id']['#selection_handler'] = 'og:default';
    $parent['target_id']['#selection_settings']['field_mode'] = 'default';

    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $parent_form = parent::form($items, $form, $form_state, $get_delta);

    $parent_form['other_groups'] = [];

    // Adding the other groups widget.
    if ($this->isGroupAdmin()) {
      $parent_form['other_groups'] = $this->otherGroupsWidget($items, $form_state);
    }

    return $parent_form;
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - AHAH-'add more' button
   * - table display and drag-n-drop value reordering.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    $target_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');

    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    $user_groups = $membership_manager->getUserGroups(\Drupal::currentUser()->id());
    $user_groups_target_type = isset($user_groups[$target_type]) ? $user_groups[$target_type] : [];
    $user_group_ids = array_map(function ($group) {
      return $group->id();
    }, $user_groups_target_type);

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = [];

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items->appendItem();
      }
      elseif (!in_array($items[$delta]->get('target_id')->getValue(), $user_group_ids)) {
        continue;
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
        }

        $elements[$delta] = $element;
      }
    }

    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, [$field_name]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => ['class' => ['field-add-more-submit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name])],
          '#submit' => [[get_class($this), 'addMoreSubmit']],
          '#ajax' => [
            'callback' => [get_class($this), 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * Adding the other groups widget to the form.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The existing items to add to the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   A renderable element with the "other groups".
   */
  protected function otherGroupsWidget(FieldItemListInterface $items, FormStateInterface $form_state) {
    if ($this->fieldDefinition->getTargetEntityTypeId() === 'user') {
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

    $delta = 0;

    $target_type = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type');

    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    $user_groups = $membership_manager->getUserGroups(\Drupal::currentUser()->id());
    $user_groups_target_type = isset($user_groups[$target_type]) ? $user_groups[$target_type] : [];
    $user_group_ids = array_map(function ($group) {
      return $group->id();
    }, $user_groups_target_type);

    $other_groups_weight_delta = round(count($user_groups) / 2);

    foreach ($items->referencedEntities() as $group) {
      if (in_array($group->id(), $user_group_ids)) {
        continue;
      }

      $elements[$delta] = $this->otherGroupsSingle($delta, $group, $other_groups_weight_delta);
      $delta++;
    }

    if (!$form_state->get('other_group_delta')) {
      $form_state->set('other_group_delta', $delta);
    }

    // Get the trigger element and check if this the add another item button.
    $trigger_element = $form_state->getTriggeringElement();

    if ($trigger_element['#name'] == 'add_another_group') {
      // Increase the number of other groups.
      $delta = $form_state->get('other_group_delta') + 1;
      $form_state->set('other_group_delta', $delta);
    }

    // Add another auto complete field.
    for ($i = $delta; $i <= $form_state->get('other_group_delta'); $i++) {
      // Also add one to the weight delta, just to make sure.
      $elements[$i] = $this->otherGroupsSingle($i, NULL, $other_groups_weight_delta + 1);
    }

    return $elements;
  }

  /**
   * Generating other groups auto complete element.
   *
   * @param int $delta
   *   The delta of the new element. Need to be the last delta in order to be
   *   added in the end of the list.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity object.
   * @param int $weight_delta
   *   The delta of the item.
   *
   * @return array
   *   A single entity reference input.
   */
  public function otherGroupsSingle($delta, EntityInterface $entity = NULL, $weight_delta = 10) {
    return [
      'target_id' => [
        // @todo Allow this to be configurable with a widget setting.
        '#type' => 'entity_autocomplete',
        '#target_type' => $this->fieldDefinition->getFieldStorageDefinition()->getSetting('target_type'),
        '#selection_handler' => 'og:default',
        '#selection_settings' => [
          'other_groups' => TRUE,
          'field_mode' => 'admin',
        ],
        '#default_value' => $entity,
      ],
      '_weight' => [
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#delta' => $weight_delta,
        '#default_value' => $delta,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Remove empty values. The form fields may be empty.
    $values = array_filter($values, function ($item) {
      return !empty($item['target_id']);
    });

    // Get the groups from the other groups widget.
    foreach ($form[$this->fieldDefinition->getName()]['other_groups'] as $key => $value) {
      if (!is_int($key)) {
        continue;
      }

      // Matches the entity label and ID. E.g. 'Label (123)'. The entity ID will
      // be captured in it's own group, with the key 'id'.
      preg_match("|.+\((?<id>[\w.]+)\)|", $value['target_id']['#value'], $matches);

      if (!empty($matches['id'])) {
        $values[] = [
          'target_id' => $matches['id'],
          '_weight' => $value['_weight']['#value'],
          '_original_delta' => $value['_weight']['#delta'],
        ];
      }
    }

    return $values;
  }

  /**
   * Determines if the current user has group admin permission.
   *
   * @return bool
   *   TRUE if the user is a group admin.
   */
  protected function isGroupAdmin() {
    // @todo Inject current user service as a dependency.
    return \Drupal::currentUser()->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION);
  }

}
