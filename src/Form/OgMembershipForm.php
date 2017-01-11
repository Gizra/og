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

    $entity = $this->getEntity();

    $form['#title'] = $this->t('Add member to %group', ['%group' => $entity->getGroup()->label()]);
    $form['entity_type'] = ['#value' => $entity->getEntityType()->id()];
    $form['entity_id'] = ['#value' => $entity->getGroup()->id()];

    if ($entity->getType() != 'default') {
      $form['membership_type'] = [
        '#title' => $this->t('Membership type'),
        '#type' => 'item',
        '#markup' => $entity->type->entity->label(),
        '#weight' => -2,
      ];
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit membership in %group', ['%group' => $entity->getGroup()->label()]);
      $form['uid']['#access'] = FALSE;
      $form['member'] = [
        '#title' => t('Member name'),
        '#type' => 'item',
        '#markup' => $entity->getUser()->getDisplayName(),
        '#weight' => -10,
      ];
    }

    return $form;
  }

}
