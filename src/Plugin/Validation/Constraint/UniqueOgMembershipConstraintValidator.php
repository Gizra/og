<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Ensures that new members added to a group do not already exist.
 *
 * Note that in typical operation, this validation constraint will not come into
 * play, as the membership entity's uid field is already validated by core's
 * ValidReferenceConstraint, which hands over to the entity reference selection
 * plugin. In our case, that is
 * \Drupal\og\Plugin\EntityReferenceSelection\OgUserSelection, which already
 * checks an existing member cannot be added to the group again.
 */
class UniqueOgMembershipConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UniqueOgMembershipConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }

    /** @var \Drupal\og\Entity\OgMembership $entity */
    $entity = $value->getEntity();

    // Only applicable to new memberships.
    if (!$entity->isNew()) {
      return;
    }

    // The default entity reference constraint adds a violation in this case.
    $value = $value->getValue();
    if (!isset($value[0]) || !isset($value[0]['target_id'])) {
      return;
    }

    $new_member_uid = $value[0]['target_id'];

    $query = $this->entityTypeManager
      ->getStorage('og_membership')
      ->getQuery()
      ->accessCheck()
      ->condition('entity_type', $entity->getGroupEntityType())
      ->condition('entity_id', $entity->getGroupId())
      ->condition('uid', $new_member_uid);
    $membership_ids = $query->execute();

    if ($membership_ids) {
      $user = $this->entityTypeManager->getStorage('user')->load($new_member_uid);
      $this->context->addViolation($constraint->notUniqueMembership, ['%user' => $user->getDisplayName()]);
      return;
    }
  }

}
