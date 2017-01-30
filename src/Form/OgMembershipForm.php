<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the group content edit forms.
 *
 * @ingroup group
 */
class OgMembershipForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\og\Entity\OgMembership $entity */
    $entity = $this->getEntity();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
    $group = $entity->getGroup();

    $form['#title'] = $this->t('Add member to %group', ['%group' => $group->label()]);
    $form['entity_type'] = ['#value' => $entity->getEntityType()->id()];
    $form['entity_id'] = ['#value' => $group->id()];

    if ($entity->getType() != 'default') {
      $form['membership_type'] = [
        '#title' => $this->t('Membership type'),
        '#type' => 'item',
        '#markup' => $entity->type->entity->label(),
        '#weight' => -2,
      ];
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit membership in %group', ['%group' => $group->label()]);
      $form['uid']['#access'] = FALSE;
      $form['member'] = [
        '#title' => t('Member name'),
        '#type' => 'item',
        '#markup' => $entity->getUser()->getDisplayName(),
        '#weight' => -10,
      ];
    }

    // Require the 'manage members' permission to be able to edit roles.
    $form['roles']['#access'] = \Drupal::service('og.access')
      ->userAccess($group, 'manage members')
      ->isAllowed();

    return $form;
  }

}
