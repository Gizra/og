<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @todo
 *
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
  public $NotValidRole = 'Invalid role selected.';

}
