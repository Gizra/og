<?php

namespace Drupal\og_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Entity\OgRole;

/**
 * Provide the group permissions form.
 */
class OgRolePermissionsForm extends OgPermissionsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_role_permissions';
  }

  /**
   * Title callback for the roles overview page.
   *
   * @param string $entity_type
   *   The group entity type id.
   * @param string $bundle
   *   The group bundle id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The role permission form title.
   */
  public function rolePermissionTitleCallback($entity_type, $bundle, $og_role) {
    $role = OgRole::load($og_role);
    return $this->t('@bundle roles - @role permissions', [
      '@bundle' => $this->entityTypeBundleInfo->getBundleInfo($entity_type)[$bundle]['label'],
      '@role' => $role->getLabel(),
    ]);
  }

  /**
   * The group role permissions form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $entity_type
   *   The group entity type id.
   * @param string $bundle
   *   The group bundle id.
   * @param string $og_role
   *   The group role id.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = '', $bundle = '', $og_role = '') {
    $role = OgRole::load($og_role);
    $this->roles = [$role->id() => $role];

    return parent::buildForm($form, $form_state, $entity_type, $bundle);
  }

}
