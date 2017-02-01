<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgRole;

/**
 * Provides an interface for OG memberships.
 */
interface OgMembershipInterface extends ContentEntityInterface {

  /**
   * Define active group content states.
   *
   * When a user has this membership state they are considered to be of
   * "member" role.
   */
  const STATE_ACTIVE = 'active';

  /**
   * Define pending group content states.
   *
   * The user is subscribed to the group but isn't an active member yet.
   *
   * When a user has this membership state they are considered to be of
   * "non-member" role.
   */
  const STATE_PENDING = 'pending';

  /**
   * Define blocked group content states. The user is rejected from the group.
   *
   * When a user has this membership state they are denied access to any
   * group related action. This state, however, does not prevent the user from
   * accessing a group or group content.
   */
  const STATE_BLOCKED = 'blocked';

  /**
   * The default group membership type that is the bundle of group membership.
   */
  const TYPE_DEFAULT = 'default';

  /**
   * The name of the user's request field in the default group membership type.
   */
  const REQUEST_FIELD = 'og_membership_request';

  /**
   * Gets the membership creation timestamp.
   *
   * @return int
   *   The membership creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the membership creation timestamp.
   *
   * @param int $timestamp
   *   The membership creation timestamp.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets the membership's owner.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setUser(AccountInterface $user);

  /**
   * Gets the membership's owner.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user object referenced by the membership.
   */
  public function getUser();

  /**
   * Sets the group associated with the membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The entity object.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setGroup(EntityInterface $group);

  /**
   * Gets the group associated with the membership.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The group object which the membership reference to.
   */
  public function getGroup();

  /**
   * Gets the group entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getGroupEntityType();

  /**
   * Gets the group entity ID.
   *
   * @return string
   *   The entity identifier.
   */
  public function getGroupId();

  /**
   * Sets the membership state.
   *
   * @param int $state
   *   The state of the membership. It may be of the following constants:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setState($state);

  /**
   * Gets the membership state.
   *
   * @return int
   *   The state of the membership. It may be of the following constants:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED
   */
  public function getState();

  /**
   * Gets the membership type.
   *
   * @return string
   *   The bundle of the membership type.
   */
  public function getType();

  /**
   * Sets the group's roles for the current user group membership.
   *
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   The array of OG roles to set.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setRoles(array $roles = []);

  /**
   * Adds a role to the user membership.
   *
   * @param \Drupal\og\Entity\OgRole $role
   *   The OG role.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function addRole(OgRole $role);

  /**
   * Revokes a role from the OG membership.
   *
   * @param \Drupal\og\Entity\OgRole $role
   *   The OG role.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function revokeRole(OgRole $role);

  /**
   * Gets all the referenced OG roles.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *   List of OG roles the user own for the current membership instance.
   */
  public function getRoles();

  /**
   * Checks if the user has a permission inside the group.
   *
   * @param string $permission
   *   The name of the permission.
   *
   * @return bool
   *   TRUE if the user has permission.
   */
  public function hasPermission($permission);

  /**
   * Returns TRUE if the OG membership is active.
   *
   * @return bool
   *   TRUE if the OG membership is active, false otherwise.
   */
  public function isActive();

  /**
   * Returns TRUE if the OG membership is pending.
   *
   * @return bool
   *   TRUE if the OG membership is pending, false otherwise.
   */
  public function isPending();

  /**
   * Returns TRUE if the OG membership is blocked.
   *
   * @return bool
   *   TRUE if the OG membership is blocked, false otherwise.
   */
  public function isBlocked();

}
