<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\OgAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidOgMembershipReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a ValidOgMembershipReferenceConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupTypeManagerInterface $group_type_manager, AccountInterface $current_user, OgAccessInterface $og_access) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupTypeManager = $group_type_manager;
    $this->currentUser = $current_user;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.group_type_manager'),
      $container->get('current_user'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }

    $group = $this->entityTypeManager
      ->getStorage($value->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type'))
      ->load($value->get('target_id')->getValue());

    if (!$group) {
      // Entity with that group ID does not exists. This could happen if a
      // stale group is passed for validation.
      return;
    }

    $params['%label'] = $group->label();

    if (!$this->groupTypeManager->isGroup($group->getEntityTypeId(), $group->bundle())) {
      $this->context->addViolation($constraint->notValidGroup, $params);
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->context->getRoot()->getValue();

    /** @var \Drupal\Core\Access\AccessResult $access */
    $user = $this->currentUser->getAccount();
    $access = $this->ogAccess->userAccessGroupContentEntityOperation('create', $group, $entity, $user);

    if ($access->isForbidden()) {
      $this->context->addViolation($constraint->notAllowedToPostInGroup, $params);
    }
  }

}
