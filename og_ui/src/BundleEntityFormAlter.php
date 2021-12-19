<?php

declare(strict_types = 1);

namespace Drupal\og_ui;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Helper for og_ui_form_alter().
 */
class BundleEntityFormAlter {

  /**
   * Alters bundle entity forms.
   *
   * @param array $form
   *   The form variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @see og_ui_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->getFormObject() instanceof BundleEntityFormBase) {
      throw new \InvalidArgumentException('Passed in form is not a bundle entity form.');
    }
    static::prepare($form);
    static::addGroupType($form, $form_state);
    static::addGroupContent($form, $form_state);
  }

  /**
   * AJAX callback displaying the target bundles select box.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state): array {
    return $form['og']['og_target_bundles'];
  }

  /**
   * Prepares object properties and adds the og details element.
   *
   * @param array $form
   *   The form variable.
   */
  protected static function prepare(array &$form): void {
    $form['og'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Organic groups'),
      '#collapsible' => TRUE,
      '#group' => 'additional_settings',
      '#description' => new TranslatableMarkup('This bundle may serve as a group, may belong to a group, or may not participate in OG at all.'),
    ];
  }

  /**
   * Adds the section to mark the entity type as a group type.
   */
  protected static function addGroupType(array &$form, FormStateInterface $form_state): void {
    $bundle = static::getEntityBundle($form_state);
    if ($bundle->isNew()) {
      $description = new TranslatableMarkup('Every entity in this bundle is a group which can contain entities and can have members.');
      $default_value = FALSE;
    }
    else {
      $description = new TranslatableMarkup('Every "%bundle" is a group which can contain entities and can have members.', [
        '%bundle' => $bundle->label(),
      ]);
      $default_value = Og::isGroup($bundle->getEntityType()->getBundleOf(), $bundle->id());
    }
    $form['og']['og_is_group'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Group'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];
  }

  /**
   * Adds the section to configure the entity type as group content.
   */
  protected static function addGroupContent(array &$form, FormStateInterface $form_state): void {
    $bundle = static::getEntityBundle($form_state);
    $entity_type_id = $bundle->getEntityType()->getBundleOf();

    // Get the stored config from the default group audience field if it exists.
    $field = FieldConfig::loadByName($entity_type_id, $bundle->id(), OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    $handler_settings = $field ? $field->getSetting('handler_settings') : [];

    // Compile a list of group entity types and bundles.
    $target_types = [];
    $target_bundles = [];
    foreach (Og::groupTypeManager()->getGroupMap() as $entity_type => $bundle_ids) {
      $target_types[$entity_type] = \Drupal::entityTypeManager()->getDefinition($entity_type)->getLabel();
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
      foreach ($bundle_ids as $bundle_id) {
        $target_bundles[$entity_type][$bundle_id] = $bundle_info[$bundle_id]['label'];
      }
    }

    $form['og']['og_group_content_bundle'] = [
      '#type' => 'checkbox',
      '#title' => new TranslatableMarkup('Group content'),
      '#default_value' => !$bundle->isNew() && Og::isGroupContent($entity_type_id, $bundle->id()),
      '#description' => empty($target_bundles) ? new TranslatableMarkup('There are no group bundles defined.') : '',
    ];

    if ($target_types) {
      // If a group audience field already exists, use its value. Otherwise fall
      // back to the first entity type that was returned.
      reset($target_types);
      $target_type_default = $field && !empty($field->getSetting('target_type')) ? $field->getSetting('target_type') : key($target_types);

      // If the target type was set using AJAX, use that instead of the default.
      $ajax_value = $form_state->getValue('og_target_type');
      $target_type_default = $ajax_value ? $ajax_value : $target_type_default;

      $form['og']['og_target_type'] = [
        '#type' => 'select',
        '#title' => new TranslatableMarkup('Target type'),
        '#options' => $target_types,
        '#default_value' => $target_type_default,
        '#description' => new TranslatableMarkup('The entity type that can be referenced through this field.'),
        '#ajax' => [
          'callback' => [static::class, 'ajaxCallback'],
          'wrapper' => 'og-settings-wrapper',
        ],
        '#states' => [
          'visible' => [
            ':input[name="og_group_content_bundle"]' => ['checked' => TRUE],
          ],
        ],
      ];

      // Get the bundles that are acting as group.
      $form['og']['og_target_bundles'] = [
        '#prefix' => '<div id="og-settings-wrapper">',
        '#suffix' => '</div>',
        '#type' => 'select',
        '#title' => new TranslatableMarkup('Target bundles'),
        '#options' => $target_bundles[$target_type_default],
        '#default_value' => !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : NULL,
        '#multiple' => TRUE,
        '#description' => new TranslatableMarkup('The bundles of the entity type that can be referenced. Optional, leave empty for all bundles.'),
        '#states' => [
          'visible' => [
            ':input[name="og_group_content_bundle"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['#validate'][] = [static::class, 'validateTargetBundleElement'];
    }
    else {
      // Don't show the settings, as there might be multiple OG audience fields
      // in the same bundle.
      $form['og']['og_group_content_bundle']['#disabled'] = TRUE;
    }
  }

  /**
   * Form validate handler.
   */
  public static function validateTargetBundleElement(array &$form, FormStateInterface $form_state): void {
    // If no checkboxes were checked for 'og_target_bundles', store NULL ("all
    // bundles are referenceable") rather than empty array ("no bundle is
    // referenceable" - typically happens when all referenceable bundles have
    // been deleted).
    if ($form_state->getValue('og_target_bundles') === []) {
      $form_state->setValue('og_target_bundles', NULL);
    }
  }

  /**
   * Retrieves the entity type bundle object from the given form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A bundle entity form.
   *
   * @return \Drupal\Core\Entity\RevisionableEntityBundleInterface
   *   The bundle.
   */
  protected static function getEntityBundle(FormStateInterface $form_state): RevisionableEntityBundleInterface {
    return $form_state->getFormObject()->getEntity();
  }
}
