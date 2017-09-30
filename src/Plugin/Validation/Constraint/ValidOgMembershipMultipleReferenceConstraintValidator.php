<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\og\Og;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Make sure that at least one audience field is populated.
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

    /** @var \Drupal\Core\Session\AccountProxy $current_user */
    $current_user = \Drupal::service('current_user');
    $account = $current_user->getAccount();
    $bundle = $entity->bundle();

    // Check if the user has site wide permission. If the the user has a site
    // wide permission we don't need to enforce the assign the content to a
    // group.
    if ($entity->isNew()) {
      $access = $account->hasPermission("create $bundle content");
    }
    else {
      $access = $account->hasPermission("edit own $bundle content") || $account->hasPermission("edit any $bundle content");
    }

    if ($access) {
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
      $this->context->addViolation('One of the fields @fields is required.', [
        '@fields' => implode(', ', $fields),
      ]);
    }
  }

}
