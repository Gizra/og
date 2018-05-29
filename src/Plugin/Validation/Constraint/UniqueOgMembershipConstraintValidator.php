<?php

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
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }

    /* @var \Drupal\og\Entity\OgMembership $entity */
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
    $membership_manager = \Drupal::service('og.membership_manager');
    foreach ($membership_manager->getGroupMemberships($entity->getGroup()) as $membership) {
      if ((string) $membership->getOwner()->id() === (string) $new_member_uid) {
        $this->context->addViolation($constraint->NotUniqueMembership, ['%user' => $membership->getOwner()->getDisplayName()]);
        return;
      }
    }
  }

}
