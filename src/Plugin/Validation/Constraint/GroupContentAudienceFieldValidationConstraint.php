<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference valid reference constraint.
 *
 * Make sure that a user without site wide permission and with group permission
 * to post group content will be forced to populate the audience field.
 *
 * @Constraint(
 *   id = "GroupContentAudienceFieldValidation",
 *   label = @Translation("Group content audience field validation", context = "Validation")
 * )
 */
class GroupContentAudienceFieldValidationConstraint extends Constraint {

  /**
   * Audience field must be populated.
   *
   * @var string
   */
  public $AudienceFieldMustBePopulated = 'You must populate the audience field %label';

}
