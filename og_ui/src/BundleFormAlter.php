<?php

/**
 * @file
 * Contains \Drupal\og_ui\BundleFormAlter.
 */

namespace Drupal\og_ui;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Helper for og_ui_form_alter().
 */
class BundleFormAlter {

  /**
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $definition;

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var string
   */
  protected $bundleLabel;

  /**
   * @var string
   */
  protected $entityTypeId;

  /**
   * Construct a BundleFormAlter object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * This is a helper for og_ui_form_alter().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {
    $this->prepare($form, $form_state);
    $this->addGroupType($form, $form_state);
    $this->addGroupContent($form, $form_state);
  }

  /**
   * AJAX callback displaying the target bundles select box.
   */
  public function ajaxCallback(array $form, array &$form_state) {
    return $form['og']['target_bundles'];
  }

  /**
   * Prepares object properties and adds the og details element.
   *
   * @param array $form
   * @param $form_state
   */
  protected function prepare(array &$form, $form_state) {
    // Example: article.
    $this->bundle = $this->entity->id();
    // Example: Article.
    $this->bundleLabel = Unicode::lcfirst($this->entity->label());
    $this->definition = $this->entity->getEntityType();
    // Example: node.
    $this->entityTypeId = $this->definition->getBundleOf();

    $form['og'] = array(
      '#type' => 'details',
      '#title' => t('Organic groups'),
      '#collapsible' => TRUE,
      '#group' => 'additional_settings',
      '#description' => t('This bundle may serve as a group, may belong to a group, or may not participate in OG at all.'),
    );
  }

  /**
   * Adds the "is group?" checkbox.
   */
  protected function addGroupType(array &$form, $form_state) {
    $form['og']['og_is_group'] = array(
      '#type' => 'checkbox',
      '#title' => t('Group'),
      '#default_value' => Og::isGroup($this->entityTypeId, $this->bundle),
      '#description' => t('Every "%bundle" is a group which can contain entities and can have members.', [
        '%bundle' => Unicode::lcfirst($this->bundleLabel),
      ]),
    );
  }

  /**
   * Adds the "is group content?" checkbox and target settings elements.
   */
  protected function addGroupContent(array &$form, $form_state) {
    $is_group_content = Og::isGroupContent($this->entityTypeId, $this->bundle);

    $target_type_default = FALSE;
    $handler_settings = [];
    if ($field = FieldConfig::loadByName($this->entityTypeId, $this->bundle, OgGroupAudienceHelper::DEFAULT_FIELD)) {
      $handler_settings = $field->getSetting('handler_settings');
      if (isset($handler_settings['target_type'])) {
        $target_type_default = $handler_settings['target_type'];
      }
    }

    $target_types = [];
    $bundle_options = [];
    $all_group_bundles = Og::groupManager()->getAllGroupBundles();
    foreach ($all_group_bundles as $group_entity_type => $bundles) {
      if (!$target_type_default) {
        $target_type_default = $group_entity_type;
      }
      $target_types[$group_entity_type] = \Drupal::entityTypeManager()
        ->getDefinition($group_entity_type)
        ->getLabel();
    }

    if ($all_group_bundles) {
      $bundle_info = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($target_type_default);
      foreach ($all_group_bundles[$target_type_default] as $bundle_name) {
        $bundle_options[$bundle_name] = $bundle_info[$bundle_name]['label'];
      }
      $description = '';
    }
    else {
      $description = t('There are no group bundles defined.');
    }

    $form['og']['og_group_content_bundle'] = array(
      '#type' => 'checkbox',
      '#title' => t('Group content'),
      '#default_value' => $is_group_content,
      '#description' => $description,
    );

    if ($target_types) {
      // Don't show the settings, as there might be multiple OG audience fields
      // in the same bundle.
      $form['og']['og_target_type'] = array(
        '#type' => 'select',
        '#title' => t('Target type'),
        '#options' => $target_types,
        '#default_value' => $target_type_default,
        '#description' => t('The entity type that can be referenced thru this field.'),
        '#ajax' => array(
          'callback' => [$this, 'ajaxCallback'],
          'wrapper' => 'og-settings-wrapper',
        ),
        '#states' => array(
          'visible' => array(
            ':input[name="og_group_content_bundle"]' => array('checked' => TRUE),
          ),
        ),
      );

      // Get the bundles that are acting as group.
      $form['og']['og_target_bundles'] = array(
        '#prefix' => '<div id="og-settings-wrapper">',
        '#suffix' => '</div>',
        '#type' => 'select',
        '#title' => t('Target bundles'),
        '#options' => $bundle_options,
        '#default_value' => isset($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : [],
        '#multiple' => TRUE,
        '#description' => t('The bundles of the entity type that can be referenced. Optional, leave empty for all bundles.'),
        '#states' => array(
          'visible' => array(
            ':input[name="og_group_content_bundle"]' => array('checked' => TRUE),
          ),
        ),
      );
    }
    else {
      $form['og']['og_group_content_bundle']['#disabled'] = TRUE;
    }
  }

}
