<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced OG role is valid.
 */
class ValidOgRoleConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }

    $entity = $value->getEntity();
    if (!$entity) {
      // Entity with that entity ID does not exists. This could happen if a
      // stale entity is passed for validation.
      return;
    }

    $group_type = $entity->getGroup()->getEntityTypeId();
    $group_bundle = $entity->getGroup()->bundle();

    foreach ($value->referencedEntities() as $og_role) {
      if ($og_role->getGroupType() !== $group_type || $og_role->getGroupBundle() !== $group_bundle) {
        $this->context->addViolation($constraint->notValidRole);
      }
    }

  }

}
