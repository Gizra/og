<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\Validation\Constraint;

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
class UniqueOgMembershipConstraintValidator extends ConstraintValidator {

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

    $query = \Drupal::service('entity_type.manager')
      ->getStorage('og_membership')
      ->getQuery()
      ->condition('entity_type', $entity->getGroupEntityType())
      ->condition('entity_id', $entity->getGroupId())
      ->condition('uid', $new_member_uid);
    $membership_ids = $query->execute();

    if ($membership_ids) {
      $user = \Drupal::service('entity_type.manager')->getStorage('user')->load($new_member_uid);
      $this->context->addViolation($constraint->notUniqueMembership, ['%user' => $user->getDisplayName()]);
      return;
    }
  }

}
