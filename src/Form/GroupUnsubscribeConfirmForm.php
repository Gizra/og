<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for unsubscribing form a group.
 */
class GroupUnsubscribeConfirmForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_unsubscribe_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var OgMembershipInterface $membership */
    $membership = $this->getEntity();
    /** @var EntityInterface $group */
    $group = $membership->getGroup();

    return $this->t('Are you sure you want to unsubscribe from the group %label?', ['%label' => $group->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Unsubscribe');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var EntityInterface $group */
    $group = $this->entity->getGroup();

    // User doesn't have access to the group entity, so redirect to front page,
    // otherwise back to the group entity.
    return $group->access('view') ? $group->toUrl() : new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var OgMembershipInterface $membership */
    $membership = $this->getEntity();
    /** @var EntityInterface $group */
    $group = $membership->getGroup();

    $redirect = $group->access('view') ? $group->toUrl() : Url::fromRoute('<front>');
    $form_state->setRedirectUrl($redirect);

    $membership->delete();
    $this->messenger()->addMessage($this->t('You have unsubscribed from the group.'));
  }

}
