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
    /** @var \Drupal\og\Entity\OgMembership $entity */
    $entity = $this->getEntity();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $group */
    $group = $entity->getGroup();

    $form = parent::form($form, $form_state);
    $form['#title'] = $this->t('Add member to %group', ['%group' => $group->label()]);
    $form['entity_type'] = ['#value' => $entity->getEntityType()->id()];
    $form['entity_id'] = ['#value' => $group->id()];

    if ($entity->getType() != 'default') {
      $form['membership_type'] = [
        '#title' => $this->t('Membership type'),
        '#type' => 'item',
        '#markup' => $entity->type->entity->label(),
        '#weight' => -2,
      ];
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit membership in %group', ['%group' => $group->label()]);
      $form['uid']['#access'] = FALSE;
      $form['member'] = [
        '#title' => t('Member name'),
        '#type' => 'item',
        '#markup' => $entity->getUser()->getDisplayName(),
        '#weight' => -10,
      ];
    }

    // Require the 'manage members' permission to be able to edit roles.
    $form['roles']['#access'] = \Drupal::service('og.access')
      ->userAccess($group, 'manage members')
      ->isAllowed();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $membership = $this->entity;
    $insert = $membership->isNew();
    $membership->save();

    $membership_link = $membership->link($this->t('View'));

    $context = [
      '@membership_type' => $membership->getType(),
      '@uid' => $membership->getUser()->id(),
      '@group_type' => $membership->getGroupEntityType(),
      '@gid' => $membership->getGroupId(),
      'link' => $membership_link,
    ];

    $t_args = [
      '%user' => $membership->getUser()->link(),
      '%group' => $membership->getGroup()->link(),
    ];

    if ($insert) {
      $this->logger('og')->notice('OG Membership: added the @membership_type membership for the use uid @uid to the group of the entity-type @group_type and ID @gid.', $context);
      drupal_set_message($this->t('Added %user to %group.', $t_args));
      return;
    }

    $this->logger('og')->notice('OG Membership: updated the @membership_type membership for the use uid @uid to the group of the entity-type @group_type and ID @gid.', $context);
    drupal_set_message($this->t('Updated the membership for %user to %group.', $t_args));
  }

}
