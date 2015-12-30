<?php

/**
 * @file
 * Contains \Drupal\og_ui\Form\GroupSubscribeConfirmForm.
 */

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgAccess;
use Drupal\og\OgMembershipInterface;

/**
 * Provides a confirmation form for subscribing form a group.
 */
class GroupSubscribeConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_ui_subscribe_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to join the group %title?', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Join');
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Indicate the OG membership state (active or pending).
    $state = OgAccess::userAccess($this->entity, 'subscribe without approval') ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;

    if ($this->entity->access('view')) {
      $label = $this->entity->label();
    }
    else {
      $label = $this->t('Private group');

      if ($state === OgMembershipInterface::STATE_ACTIVE) {
        // Determine if a user can subscribe to a private group, when OG-access
        // module is enabled, and the group is set to private.
        $state = $this->config('og_ui.settings')->get('deny_subscribe_without_approval') ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;
      }
    }

//    // Add group membership form.
//    $og_membership = og_membership_create($group_type, $gid, 'user', $account->uid, $field_name, array('state' => $state));
//    $form_state['og_membership'] = $og_membership;
//    field_attach_form('og_membership', $og_membership, $form, $form_state);
//
//    if ($state == OG_STATE_ACTIVE && !empty($form[OG_MEMBERSHIP_REQUEST_FIELD])) {
//      // Hide the user request field.
//      $form[OG_MEMBERSHIP_REQUEST_FIELD]['#access'] = FALSE;
//    }
//    $form['group_type'] = array('#type' => 'value', '#value' => $group_type);
//    $form['gid'] = array('#type' => 'value', '#value' => $gid);
//    $form['field_name'] = array('#type' => 'value', '#value' => $field_name);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // @see entity_form_field_validate().
    $og_membership = $form_state['og_membership'];
    field_attach_form_validate('og_membership', $og_membership, $form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $og_membership = $form_state['og_membership'];
    field_attach_submit('og_membership', $og_membership, $form, $form_state);

    $og_membership->save();

    if ($this->entity->access('view')) {
      $form_state->setRedirectUrl($this->entity->toUrl());
    }
    else {
      // User doesn't have access to the group entity, so redirect to front page,
      // with a message.
      drupal_set_message($this->t('Your subscription request was sent.'));
    }
  }

}

