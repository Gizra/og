<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\og\Og;
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

    $entity = $value->getEntity();
    // Only applicable to new memberships.
    if (!$entity->isNew()) {
      return;
    }

    $new_member_uid = $value->getValue()[0]['target_id'];
    $membership_manager = \Drupal::service('og.membership_manager');
    foreach ($membership_manager->getGroupMemberships($entity->getGroup()) as $membership) {
      if ((string) $membership->getUser()->id() === (string) $new_member_uid) {
        $this->context->addViolation($constraint->NotUniqueMembership, ['%user' => $membership->getUser()->getDisplayName()]);
        return;
      }
    }
  }

}
