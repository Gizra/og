<?php

declare(strict_types = 1);

namespace Drupal\og_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Og;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Helper for og_access_form_alter().
 */
class OgAccessBundleFormAlter {
  use StringTranslationTrait;

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
   * @param Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation object.
   */
  public function __construct(EntityInterface $entity, TranslationInterface $string_translation) {
    $this->entity = $entity;
    $this->stringTranslation = $string_translation;
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
      '#title' => $this->t('Restrict access to group members'),
      '#description' => $this->t('Enable OG access control. Provides a new field that determines the group/group content visibility. Public groups can have member-only content. Any public group content belonging to a private group will be restricted to the members of that group only.'),
      '#default_value' => $this->bundle ? $this->hasAccessControl() : FALSE,
      '#states' => [
        'visible' => [
          [':input[name="og_is_group"]' => ['checked' => TRUE]],
          [':input[name="og_group_content_bundle"]' => ['checked' => TRUE]],
        ],
      ],
    ];
  }

  /**
   * Checks whether the existing bundle has OG access control enabled.
   *
   * @return bool
   *   True if the group bundle has the OgAccess::OG_ACCESS_FIELD field -OR-
   *        if the group content bundle has the OG_CONTENT_ACCESS_FIELD field.
   *   False otherwise.
   */
  protected function hasAccessControl() {
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($this->entityTypeId, $this->bundle);

    if (Og::isGroup($this->entityTypeId, $this->bundle)) {
      return isset($field_definitions[OgAccess::OG_ACCESS_FIELD]);
    }

    if (Og::isGroupContent($this->entityTypeId, $this->bundle)) {
      return isset($field_definitions[OgAccess::OG_ACCESS_CONTENT_FIELD]);
    }

    return FALSE;
  }

}
