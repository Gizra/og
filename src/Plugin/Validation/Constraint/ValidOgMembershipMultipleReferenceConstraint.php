<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid references constraint.
 *
 * Make sure that when user that can only post content inside a group populated
 * one of the audience fields. After the fields are populated the constraint for
 * checking valid references will make sure the values are OK.
 *
 * @Constraint(
 *   id = "ValidOgMembershipMultipleReference",
 *   label = @Translation("Organic Groups valid reference", context = "Validation")
 * )
 */
class ValidOgMembershipMultipleReferenceConstraint extends Constraint {

  /**
   * Not a valid group message.
   *
   * @var string
   */
  public $NotValidGroup = 'The entity %label is not defined as a group.';

  /**
   * Not a valid group message.
   *
   * @var string
   */
  public $NotAllowedToPostInGroup = 'You are not allowed to post content in the group %label';

}
