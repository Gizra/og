<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgAccess;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for subscribing to a group.
 */
class GroupSubscribeForm extends ContentEntityForm {

  /**
   * OG access service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * Constructs a SubscriptionController object.
   */
  public function __construct(OgAccess $og_access) {
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_subscribe_confirm_form';
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
    /** @var OgMembershipInterface $membership */
    $membership = $this->entity;
    $user = $membership->getUser();
    $group = $membership->getGroup();

    $state = $this->ogAccess->userAccess($group, 'subscribe without approval', $user) ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $membership = $this->entity;
    $membership->save();

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
