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

    $access_control = \Drupal::entityTypeManager()->getAccessControlHandler($entity->getEntityTypeId());

    $account = $current_user->getAccount();
    $account->skip_og_permission = TRUE;

    $access = $entity->isNew() ?
      $access_control->createAccess($entity->bundle(), $current_user->getAccount(), ['skip_og_permission' => TRUE]) :
      $access_control->access($entity, 'update', $account);

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
