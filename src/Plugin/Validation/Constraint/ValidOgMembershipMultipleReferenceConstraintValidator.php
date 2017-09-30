<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\og\Og;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidOgMembershipMultipleReferenceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /* @var \Drupal\Core\Entity\ContentEntityBase $entity */
    if (!Og::isGroupContent($entity->getEntityTypeId(), $entity->bundle())) {
      return;
    }

    $audience_fields = \Drupal::service('og.group_audience_helper')->getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle());

    if (count($audience_fields) === 1) {
      // There is only one group audience field. Setting the field as required
      // is done in another place.
      return;
    }

    $fields_are_empty = TRUE;
    $fields = [];

    foreach ($audience_fields as $audience_field => $info) {
      $fields[] = $info->getLabel();

      // Check that the fields are not empty.
      foreach ($entity->get($audience_field)->getValue() as $value) {
        if (!empty($value)) {
          $fields_are_empty = FALSE;
        }
      }
    }

    if ($fields_are_empty) {
      $this->context->addViolation('The fields @fields cannot be empty!', [
        '@fields' => implode(', ', $fields),
      ]);
    }
  }

}
