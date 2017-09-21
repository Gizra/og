<?php

namespace Drupal\og_ui;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Helper for og_ui_form_alter().
 */
class BundleFormAlter {

  /**
   * Entity type definition.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $definition;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The bundle label.
   *
   * @var string
   */
  protected $bundleLabel;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The form entity which has been used for populating form element defaults.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Construct a BundleFormAlter object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * This is a helper for og_ui_form_alter().
   *
   * @param array $form
   *   The form variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {
    $this->prepare($form, $form_state);
    $this->addGroupType($form, $form_state);
    $this->addGroupContent($form, $form_state);
  }

  /**
   * AJAX callback displaying the target bundles select box.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['og']['og_target_bundles'];
  }

  /**
   * Prepares object properties and adds the og details element.
   *
   * @param array $form
   *   The form variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function prepare(array &$form, FormStateInterface $form_state) {
    // Example: article.
    $this->bundle = $this->entity->id();
    // Example: Article.
    $this->bundleLabel = Unicode::lcfirst($this->entity->label());
    $this->definition = $this->entity->getEntityType();
    // Example: node.
    $this->entityTypeId = $this->definition->getBundleOf();

    $form['og'] = [
      '#type' => 'details',
      '#title' => t('Organic groups'),
      '#collapsible' => TRUE,
      '#group' => 'additional_settings',
      '#description' => t('This bundle may serve as a group, may belong to a group, or may not participate in OG at all.'),
    ];
  }

  /**
   * Adds the "is group?" checkbox.
   */
  protected function addGroupType(array &$form, FormStateInterface $form_state) {
    if ($this->entity->isNew()) {
      $description = t('Every entity in this bundle is a group which can contain entities and can have members.');
    }
    else {
      $description = t('Every "%bundle" is a group which can contain entities and can have members.', [
        '%bundle' => Unicode::lcfirst($this->bundleLabel),
      ]);
    }
    $form['og']['og_is_group'] = [
      '#type' => 'checkbox',
      '#title' => t('Group'),
      '#default_value' => Og::isGroup($this->entityTypeId, $this->bundle),
      '#description' => $description,
    ];
  }

  /**
   * Adds the "is group content?" checkbox and target settings elements.
   */
  protected function addGroupContent(array &$form, FormStateInterface $form_state) {
    // Get the stored config from the default group audience field if it exists.
    $field = FieldConfig::loadByName($this->entityTypeId, $this->bundle, OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    $handler_settings = $field ? $field->getSetting('handler_settings') : [];

    // Compile a list of group entity types and bundles.
    $target_types = [];
    $target_bundles = [];
    foreach (Og::groupTypeManager()->getAllGroupBundles() as $entity_type => $bundles) {
      $target_types[$entity_type] = \Drupal::entityTypeManager()->getDefinition($entity_type)->getLabel();
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
      foreach ($bundles as $bundle) {
        $target_bundles[$entity_type][$bundle] = $bundle_info[$bundle]['label'];
      }
    }

    $form['og']['og_group_content_bundle'] = [
      '#type' => 'checkbox',
      '#title' => t('Group content'),
      '#default_value' => $this->bundle ? Og::isGroupContent($this->entityTypeId, $this->bundle) : FALSE,
      '#description' => empty($target_bundles) ? t('There are no group bundles defined.') : '',
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
        '#title' => t('Target type'),
        '#options' => $target_types,
        '#default_value' => $target_type_default,
        '#description' => t('The entity type that can be referenced through this field.'),
        '#ajax' => [
          'callback' => [$this, 'ajaxCallback'],
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
        '#title' => t('Target bundles'),
        '#options' => $target_bundles[$target_type_default],
        '#default_value' => !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : NULL,
        '#multiple' => TRUE,
        '#description' => t('The bundles of the entity type that can be referenced. Optional, leave empty for all bundles.'),
        '#states' => [
          'visible' => [
            ':input[name="og_group_content_bundle"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['#validate'][] = [get_class($this), 'validateTargetBundleElement'];
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
  public static function validateTargetBundleElement(array &$form, FormStateInterface $form_state) {
    // If no checkboxes were checked for 'og_target_bundles', store NULL ("all
    // bundles are referenceable") rather than empty array ("no bundle is
    // referenceable" - typically happens when all referenceable bundles have
    // been deleted).
    if ($form_state->getValue('og_target_bundles') === []) {
      $form_state->setValue('og_target_bundles', NULL);
    }
  }

}
