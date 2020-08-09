<?php

namespace Drupal\og\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for subscribing to a group.
 *
 * As this form in fact saves the OG membership it is easier to use
 * Drupal\Core\Entity\ContentEntityForm
 * However we mimic the functionality of
 * Drupal\Core\Entity\EntityConfirmFormBase, so it will be presented as a
 * confirmation page.
 */
class GroupSubscribeForm extends ContentEntityForm {

  /**
   * OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a GroupSubscribeForm.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   *
   * @todo Set the `EntityRepositoryInterface` type hint on the second argument
   *   once Drupal 8.6.0 is released. It is currently omitted to preserve
   *   backwards compatibility with Drupal 8.5.x and earlier.
   *
   * @see https://github.com/Gizra/og/issues/397
   */
  public function __construct(OgAccessInterface $og_access, EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access'),
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_subscribe_confirm_form';
  }

  /**
   * Get the question to present to the user according to the membership state.
   *
   * @return string
   *   The confirmation question.
   */
  public function getQuestion() {
    /** @var \Drupal\og\OgMembershipInterface $membership */
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
   * Get confirmation text, according to the membership state.
   *
   * @return string
   *   The text.
   */
  public function getConfirmText() {
    return $this->isStateActive() ? $this->t('Join') : $this->t('Request membership');
  }

  /**
   * Return the URL to redirect on cancel.
   *
   * @return \Drupal\Core\Url
   *   The URL object to redirect to.
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
   *
   * @see \Drupal\Core\Entity\EntityConfirmFormBase::buildForm
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = $this->isStateActive() ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entity;
    $membership->setState($state);

    // Add confirmation related elements.
    $form['#title'] = $this->getQuestion();

    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = [
      '#markup' => $this->t('This action cannot be undone.'),
    ];

    $form['confirm'] = [
      '#type' => 'hidden',
      '#value' => 1,
    ];

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }

    $form = parent::buildForm($form, $form_state);

    if ($this->isStateActive() && !empty($form[OgMembershipInterface::REQUEST_FIELD])) {
      // State is active, so no need to show the request field, as the user
      // will not need any approval for joining.
      $form[OgMembershipInterface::REQUEST_FIELD]['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\EntityConfirmFormBase::actions
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->getConfirmText();
    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->getCancelUrl(),
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];
    return $actions;
  }

  /**
   * Determine if the membership state should be active.
   *
   * @return bool
   *   True if the state is active.
   */
  public function isStateActive() {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->getEntity();

    /** @var EntityInterface $group */
    $group = $membership->getGroup();
    $user = $membership->getOwner();

    $skip_approval = $this->ogAccess->userAccess($group, 'subscribe without approval', $user)->isAllowed();

    $state = $skip_approval ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_PENDING;

    if (!$group->access('view', $user) && $state === OgMembershipInterface::STATE_ACTIVE) {
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->getEntity();

    /** @var EntityInterface $group */
    $group = $membership->getGroup();

    $message = $membership->isActive() ? $this->t('You are now subscribed to the group.') : $this->t('Your subscription request has been sent.');
    $this->messenger()->addMessage($message);

    // User doesn't have access to the group entity, so redirect to front page,
    // otherwise back to the group entity.
    $redirect = $group->access('view') ? $group->toUrl() : Url::fromRoute('<front>');
    $form_state->setRedirectUrl($redirect);

  }

}
