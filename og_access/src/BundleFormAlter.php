<?php

namespace Drupal\og_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Og;

/**
 * Helper for og_access_form_alter().
 */
class BundleFormAlter {

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

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
    // Example: node.
    $this->entityTypeId = $this->entity->getEntityType()->getBundleOf();

    // Example: article.
    $this->bundle = $this->entity->id();

    $form['og']['og_enable_access'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable OG access control'),
      '#default_value' => $this->hasAccessControl(),
      '#states' => [
        'visible' => [
          [':input[name="og_is_group"]' => ['checked' => TRUE]],
          [':input[name="og_group_content_bundle"]' => ['checked' => TRUE]],
        ]
      ],
    ];
  }

  /**
   * Checks whether the existing bundle has OG access control enabled.
   *
   * @return bool
   *   True if the group bundle has the OG_ACCESS_FIELD field -OR-
   *        if the group content bundle has the OG_CONTENT_ACCESS_FIELD field.
   *   False otherwise.
   */
  protected function hasAccessControl() {
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($this->entityTypeId, $this->bundle);

    if (Og::isGroup($this->entityTypeId, $this->bundle)) {
      return isset($field_definitions[OG_ACCESS_FIELD]);
    }

    if (Og::isGroupContent($this->entityTypeId, $this->bundle)) {
      return isset($field_definitions[OG_CONTENT_ACCESS_FIELD]);
    }

    return FALSE;
  }

}
