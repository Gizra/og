<?php

/**
 * @file
 * Contains \Drupal\og_ui\Form\GroupUnsubscribeConfirmForm.
 */

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for unsubscribing form a group.
 */
class GroupUnsubscribeConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_ui_unsubscribe_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unsubscribe from the group %title?', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // TODO: Implement getCancelUrl() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo This needs to be converted to D8.
    //og_ungroup($this->entity);

    if ($this->entity->access('view')) {
      $form_state->setRedirectUrl($this->entity->toUrl());
    }
    else {
      $form_state->setRedirectUrl(Url::fromRoute('<front>'));
    }
  }

}
