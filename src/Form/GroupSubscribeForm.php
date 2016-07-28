<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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

    $message = $this->isStateActive()
      ? $this->t('Are you sure you want to join the group %label?', ['%label' => $label])
      : $this->t('Are you sure you want to request subscription the group %label?', ['%label' => $label]);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->isStateActive() ? $this->t('Join') : $this->t('Request membership');
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = $this->isStateActive() ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;
    $this->entity->setState($state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Determine if the membership state should be active.
   *
   * @return bool
   *   True if the state is active.
   */
  protected function isStateActive() {
    /** @var OgMembershipInterface $membership */
    $membership = $this->entity;

    /** @var EntityInterface $group */
    $group = $this->entity->getGroup();

    $state = $this->ogAccess->userAccess($group, 'subscribe without approval') ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;

    if (!$group->access('view') && $membership->getState() === OgMembershipInterface::STATE_ACTIVE) {
      // Determine with which state a user can subscribe to a group they don't
      // have access to.
      // By default, for security reasons, if the group is private, then the
      // state would be pending, regardless if the "subscribe without approval"
      // is enabled.
      $state = $this->config('og.settings')->get('deny_subscribe_without_approval') ? OgMembershipInterface::STATE_PENDING : OgMembershipInterface::STATE_ACTIVE;
    }

    return $state === OgMembershipInterface::STATE_ACTIVE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: We will need to change this when we have configurable fields.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
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
    drupal_set_message($message);

    // User doesn't have access to the group entity, so redirect to front page,
    // otherwise back to the group entity.
    $redirect = $group->access('view') ? $group->toUrl() : new Url('<front>');
    $form_state->setRedirectUrl($redirect);

  }

}
