<?php

namespace Drupal\og\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\og\OgMembershipInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for subscribing a user to a group.
 */
class GroupUserSubscribeForm extends ContentEntityForm {

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
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|\Drupal\Core\Entity\EntityManagerInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
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
    return 'og_user_subscribe_form';
  }

  /**
   * Return the URL to redirect on cancel.
   *
   * @return \Drupal\Core\Url
   *   The URL object to redirect to.
   */
  public function getCancelUrl() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
    $group = $this->entity->getGroup();

    // User have access to the group entity, direct to the member admin page.
    // Otherwise back to the front page.
    if ($group->access('view')) {
      return new Url("entity.{$group->getEntityTypeId()}.og_admin_routes.members", [$group->getEntityTypeId() => $group->id()]);
    }
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\EntityConfirmFormBase::buildForm
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User'),
      '#target_type' => 'user',
    ];
    $form[OgMembershipInterface::REQUEST_FIELD]['#access'] = FALSE;
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Add');
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entity;
    $this->messenger()->addStatus($this->t('%name added to %group.', [
      '%name' => $membership->getOwner()->getAccountName(),
      '%group' => $membership->getGroup()->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());

  }

}
