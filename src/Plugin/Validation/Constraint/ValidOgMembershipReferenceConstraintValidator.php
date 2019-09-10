<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\og\Og;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidOgMembershipReferenceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }

    $group = \Drupal::entityTypeManager()
      ->getStorage($value->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type'))
      ->load($value->get('target_id')->getValue());

    if (!$group) {
      // Entity with that group ID does not exists. This could happen if a
      // stale group is passed for validation.
      return;
    }

    $params['%label'] = $group->label();

    if (!Og::isGroup($group->getEntityTypeId(), $group->bundle())) {
      $this->context->addViolation($constraint->NotValidGroup, $params);
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->context->getRoot()->getValue();

    /** @var \Drupal\Core\Access\AccessResult $access */
    // @todo: Refactor the permission format in #510.
    $permission = "create {$entity->bundle()} content";
    $user = \Drupal::currentUser()->getAccount();
    $access = \Drupal::service('og.access')->userAccessEntity($permission, $group, $user);

    if ($access->isForbidden()) {
      $this->context->addViolation($constraint->NotAllowedToPostInGroup, $params);
    }
  }

}
