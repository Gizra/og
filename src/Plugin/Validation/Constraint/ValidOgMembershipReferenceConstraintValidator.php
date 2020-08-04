<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\og\OgAccessInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidOgMembershipReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(AccountInterface $current_user, OgAccessInterface $og_access) {
    $this->currentUser = $current_user;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInjectionInterface $container) {
    return new static(
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

    $group = \Drupal::entityTypeManager()
      ->getStorage($value->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type'))
      ->load($value->get('target_id')->getValue());

    if (!$group) {
      // Entity with that group ID does not exists. This could happen if a
      // stale group is passed for validation.
      return;
    }

    $params['%label'] = $group->label();

    if (!Og::isGroup($group->getEntityTypeId(), $group->bundle())) {
      $this->context->addViolation($constraint->notValidGroup, $params);
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->context->getRoot()->getValue();

    /** @var \Drupal\Core\Access\AccessResult $access */
    $user = \Drupal::currentUser()->getAccount();
    $access = \Drupal::service('og.access')->userAccessGroupContentEntityOperation('create', $group, $entity, $user);

    if ($access->isForbidden()) {
      $this->context->addViolation($constraint->notAllowedToPostInGroup, $params);
    }
  }

}
