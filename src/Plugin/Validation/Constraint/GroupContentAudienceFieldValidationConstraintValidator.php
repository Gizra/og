<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\og\Og;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class GroupContentAudienceFieldValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->context->getRoot()->getValue();

    if (!Og::groupTypeManager()->isGroupContent($entity->getEntityTypeId(), $entity->bundle())) {
      // The host entity is a a group content type. Skip this one.
      return;
    }

    // Get all the fields and check if we have values inside them.
    $fields = \Drupal::service('og.group_audience_helper')->getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle());
    foreach (array_keys($fields) as $field) {
      if ($entity->get($field)->target_id) {
        // We found value in one of the fields. That's mean the node won't
        // create without a reference to a group while the user must populate
        // the field.
        return;
      }
    }

    if ($entity->access('create')) {
      // No values in the fields but the user have site wide permission.
      return;
    }

    $this->context->addViolation($constraint->AudienceFieldMustBePopulated);
  }

}
