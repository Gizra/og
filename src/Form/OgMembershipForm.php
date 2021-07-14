<?php

declare(strict_types = 1);

namespace Drupal\og\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the group content edit forms.
 *
 * @ingroup group
 */
class OgMembershipForm extends ContentEntityForm {

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * Constructs a MessageForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, OgAccessInterface $og_access) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('og.access')
    );
  }

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

    if ($entity->getType() != OgMembershipInterface::TYPE_DEFAULT) {
      $form['membership_type'] = [
        '#title' => $this->t('Membership type'),
        '#type' => 'item',
        '#plain_text' => $entity->type->entity->label(),
        '#weight' => -2,
      ];
    }

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit membership in %group', ['%group' => $group->label()]);
      $form['uid']['#access'] = FALSE;
      $form['member'] = [
        '#title' => $this->t('Member name'),
        '#type' => 'item',
        '#markup' => $entity->getOwner()->getDisplayName(),
        '#weight' => -10,
      ];
    }

    // Require the 'manage members' permission to be able to edit roles.
    $form['roles']['#access'] = $this->ogAccess
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

    $membership_link = $membership->toLink($this->t('View'))->toString();

    $context = [
      '@membership_type' => $membership->getType(),
      '@uid' => $membership->getOwner()->id(),
      '@group_type' => $membership->getGroupEntityType(),
      '@gid' => $membership->getGroupId(),
      'link' => $membership_link,
    ];

    $t_args = [
      '%user' => $membership->getOwner()->toLink()->toString(),
      '%group' => $membership->getGroup()->toLink()->toString(),
    ];

    if ($insert) {
      $this->logger('og')->notice('OG Membership: added the @membership_type membership for the use uid @uid to the group of the entity-type @group_type and ID @gid.', $context);
      $this->messenger()->addMessage($this->t('Added %user to %group.', $t_args));
      return;
    }

    $this->logger('og')->notice('OG Membership: updated the @membership_type membership for the use uid @uid to the group of the entity-type @group_type and ID @gid.', $context);
    $this->messenger()->addMessage($this->t('Updated the membership for %user to %group.', $t_args));
  }

}
