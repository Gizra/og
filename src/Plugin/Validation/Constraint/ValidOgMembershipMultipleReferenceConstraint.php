<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid references constraint.
 *
 * Make sure that when a user that can only post content inside a group
 * populated one of the audience fields. After the fields are populated the
 * constraint for checking valid references will make sure the values are OK.
 *
 * @Constraint(
 *   id = "ValidOgMembershipMultipleReference",
 *   label = @Translation("Organic Groups valid references", context = "Validation")
 * )
 */
class ValidOgMembershipMultipleReferenceConstraint extends Constraint {

  /**
   * Fields are not populated.
   *
   * @var string
   */
  public $audienceFieldsAreNotPopulated = 'One of the fields @fields is required.';

}
