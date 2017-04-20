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

    dpm($entity->access('create'));

    $params['%label'] = 'foo';
    $this->context->addViolation($constraint->AudienceFieldMustBePopulated, $params);
  }

}
