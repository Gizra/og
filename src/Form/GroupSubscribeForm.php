<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgAccess;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for subscribing to a group.
 */
class GroupSubscribeForm extends EntityConfirmFormBase {

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
    /** @var OgMembershipInterface $membership */
    $membership = $this->entity;
    /** @var EntityInterface $group */
    $group = $membership->getGroup();

    $label = $group->access('view') ? $group->label() : $this->t('Private group');

    return $this->t('Are you sure you want to join the group %label?', ['%label' => $label]);
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
    /** @var EntityInterface $group */
    $group = $membership->getGroup();

    $state = $this->ogAccess($group, 'subscribe without approval') ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;

    if (!$group->access('view') && $membership->getState() === OgMembershipInterface::STATE_ACTIVE) {
      // Determine if a user can subscribe to a private group, when OG-access
      // module is enabled, and the group is set to private.
      $state = $this->config('og_ui.settings')->get('deny_subscribe_without_approval') ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;
    }

    $this->entity->setState($state);

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
    parent::submitForm($form, $form_state);
    /** @var OgMembershipInterface $membership */
    $membership = $this->entity;
    $membership->save();

    /** @var EntityInterface $group */
    $group = $membership->getGroup();

    $message = $membership->getState() === OgMembershipInterface::STATE_ACTIVE ? $this->t('Your are now subscribed to the group.') : $this->t('Your subscription request was sent.');

    // User doesn't have access to the group entity, so redirect to front page,
    // otherwise back to the group entity.
    $redirect = $group->access('view') ? $group->toUrl() : '<front>';


    drupal_set_message($message);
    $form_state->setRedirectUrl($redirect);

  }

}
