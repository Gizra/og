<?php

namespace Drupal\Tests\og\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;

/**
 * Provides a method to create memberships for testing purposes.
 *
 * This trait is meant to be used only by test classes.
 */
trait OgMembershipCreationTrait {

  /**
   * Creates a test membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group for which to create the membership.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user for which to create the membership.
   * @param array $role_names
   *   Optional array of role names to assign to the membership. Defaults to the
   *   'member' role.
   * @param string $state
   *   Optional membership state. Can be one of the following constants:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED
   *   Defaults to OgMembershipInterface::STATE_ACTIVE.
   * @param string $membership_type
   *   The membership type. Defaults to 'default'.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The membership.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when the membership cannot be created.
   */
  protected function createOgMembership(EntityInterface $group, AccountInterface $user, array $role_names = NULL, $state = NULL, $membership_type = NULL) {
    // Provide default values.
    $role_names = $role_names ?: [OgRoleInterface::AUTHENTICATED];
    $state = $state ?: OgMembershipInterface::STATE_ACTIVE;
    $membership_type = $membership_type ?: OgMembershipInterface::TYPE_DEFAULT;

    $group_entity_type = $group->getEntityTypeId();
    $group_bundle = $group->bundle();

    $roles = array_map(function ($role_name) use ($group_entity_type, $group_bundle) {
      return OgRole::getRole($group_entity_type, $group_bundle, $role_name);
    }, $role_names);

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => $membership_type]);
    $membership
      ->setRoles($roles)
      ->setState($state)
      ->setOwner($user)
      ->setGroup($group)
      ->save();

    return $membership;
  }

}
