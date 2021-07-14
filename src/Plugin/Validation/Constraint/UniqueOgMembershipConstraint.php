<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 *
 * @Constraint(
 *   id = "UniqueOgMembership",
 *   label = @Translation("Unique OG membership", context = "Validation")
 * )
 */
class UniqueOgMembershipConstraint extends Constraint {

  /**
   * Not a unique membership group message.
   *
   * @var string
   */
  public $notUniqueMembership = 'The user %user is already a member in this group';

}
