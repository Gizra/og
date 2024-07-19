<?php

declare(strict_types=1);

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures that a valid OG role for the group entity type/bundle is selected.
 *
 * @Constraint(
 *   id = "ValidOgRole",
 *   label = @Translation("OG Role valid reference", context = "Validation")
 * )
 */
class ValidOgRoleConstraint extends Constraint {

  /**
   * Not a valid role message.
   *
   * @var string
   */
  public $notValidRole = 'Invalid role selected.';

}
