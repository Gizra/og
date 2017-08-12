<?php

namespace Drupal\og\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og\Entity\OgRole;

/**
 * Provides a form to add multiple OG roles to a membership.
 *
 * @see \Drupal\og\Plugin\Action\AddMultipleOgMembershipRoles
 */
class OgAddMultipleRolesForm extends OgChangeMultipleRolesFormBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_membership_add_multiple_roles_action';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->getGroupTypes() as $group_type) {
      /** @var \Drupal\og\OgRoleInterface $role */
      foreach (OgRole::loadByGroupType($group_type['entity_type_id'], $group_type['bundle_id']) as $role) {
        // Only add the role to the list if it is not a required role, these
        // cannot be added.
        if (!$role->isRequired()) {
          $options[$role->id()] = $role->label();
        }
      }
    }

    $form['roles'] = [
      '#type' => 'select',
      '#title' => t('Add roles'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#options' => $options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $role_ids = array_keys($form_state->getValue('roles'));
    /** @var \Drupal\og\OgRoleInterface[] $roles */
    $roles = OgRole::loadMultiple($role_ids);
    foreach ($this->getMemberships() as $membership) {
      $changed = FALSE;
      foreach ($roles as $role) {
        $group = $membership->getGroup();
        if ($group->getEntityTypeId() === $role->getGroupType() && $group->bundle() === $role->getGroupBundle()) {
          // Only add the role to the membership if it is valid and doesn't
          // exist yet.
          if ($membership->isRoleValid($role) && !$membership->hasRole($role->id())) {
            $changed = TRUE;
            $membership->addRole($role);
          }
        }
      }
      // Only save the membership if it has actually changed.
      if ($changed) {
        $membership->save();
      }
    }
  }

}
