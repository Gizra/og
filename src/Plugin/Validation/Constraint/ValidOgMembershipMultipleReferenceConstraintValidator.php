<?php

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\og\Og;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Drupal\og\OgGroupAudienceHelperInterface;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Make sure that at least one audience field is populated.
 */
class ValidOgMembershipMultipleReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * Constructs a ValidOgMembershipMultipleReferenceConstraintValidator object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\og\OgGroupAudienceHelperInterface $group_audience_helper
   *   The OG group audience helper.
   */
  public function __construct(AccountInterface $current_user, OgGroupAudienceHelperInterface $group_audience_helper) {
    $this->currentUser = $current_user;
    $this->groupAudienceHelper = $group_audience_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('og.group_audience_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /* @var \Drupal\Core\Entity\ContentEntityBase $entity */
    if (!Og::isGroupContent($entity->getEntityTypeId(), $entity->bundle())) {
      return;
    }

    /** @var \Drupal\Core\Session\AccountProxy $current_user */
    $account = $this->currentUser->getAccount();
    $bundle = $entity->bundle();

    // Check if the user has site wide permission. If the user has global access
    // we don't need to do any further checks.
    if ($entity->isNew()) {
      $access = $account->hasPermission("create $bundle content");
    }
    else {
      $access = $account->hasPermission("edit own $bundle content") || $account->hasPermission("edit any $bundle content");
    }

    if ($access) {
      return;
    }

    $audience_fields = $this->groupAudienceHelper->getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle());

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
