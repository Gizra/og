<?php

declare(strict_types = 1);

namespace Drupal\og\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;

/**
 * Provides a form for subscribing a user to a group.
 */
class GroupUserSubscribeForm extends ContentEntityForm {

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
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entity;
    $form['uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Member'),
      '#target_type' => 'user',
    ];
    $options = [];
    /** @var \Drupal\og\OgRoleInterface $role */
    foreach (OgRole::loadByGroupType($membership->getGroup()->getEntityTypeId(), $membership->getGroup()->bundle()) as $role) {
      // Only add the role to the list if it is not a required role, these
      // cannot be added. Nor should invalid roles be added.
      if (!$role->isRequired() && $membership->isRoleValid($role)) {
        $options[$role->id()] = $role->label();
      }
    }
    $form['roles'] = [
      '#type' => 'select',
      '#title' => t('Roles'),
      '#multiple' => TRUE,
      '#options' => $options,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entity;
    $result = $this->entityTypeManager
      ->getStorage($membership->getEntityTypeId())
      ->getQuery()
      ->condition('uid', $form_state->getValue('uid'))
      ->condition('type', $membership->bundle())
      ->condition('entity_type', $membership->getGroup()->getEntityTypeId())
      ->condition('entity_id', $membership->getGroup()->id())
      ->execute();
    if ($result) {
      $form_state->setErrorByName('uid', $this->t('The user is already a member of the group.'));
    }
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
