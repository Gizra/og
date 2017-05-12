<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Ensures that new members added to a group do not already exist.
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
      if ((string) $membership->getUser()->id() === (string) $new_member_uid) {
        $this->context->addViolation($constraint->NotUniqueMembership, ['%user' => $membership->getUser()->getDisplayName()]);
        return;
      }
    }
  }

}
