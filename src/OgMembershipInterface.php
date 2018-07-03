<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for OG memberships.
 */
interface OgMembershipInterface extends ContentEntityInterface, EntityOwnerInterface {

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
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The group object which is referenced by the membership, or NULL if no
   *   group has been set yet.
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
   * @param string $state
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
   * @return string
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
   * @param \Drupal\og\OgRoleInterface $role
   *   The OG role.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function addRole(OgRoleInterface $role);

  /**
   * Revokes a role from the OG membership.
   *
   * @param \Drupal\og\OgRoleInterface $role
   *   The OG role.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function revokeRole(OgRoleInterface $role);

  /**
   * Revokes a role from the OG membership.
   *
   * @param string $role_id
   *   The OG role ID.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function revokeRoleById($role_id);

  /**
   * Gets all the referenced OG roles.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *   List of OG roles the user own for the current membership instance.
   */
  public function getRoles();

  /**
   * Gets all the referenced OG role IDs.
   *
   * @return string[]
   *   List of OG role IDs that are granted in the membership.
   */
  public function getRolesIds();

  /**
   * Returns whether the given role is valid for this membership.
   *
   * @param \Drupal\og\OgRoleInterface $role
   *   The role to check.
   *
   * @return bool
   *   True if the role is valid, false otherwise.
   *
   * @throws \LogicException
   *   Thrown when the validity of the role cannot be established, for example
   *   because the group hasn't yet been set on the membership.
   */
  public function isRoleValid(OgRoleInterface $role);

  /**
   * Checks if the membership has the role with the given ID.
   *
   * @param string $role_id
   *   The ID of the role to check.
   *
   * @return bool
   *   True if the membership has the role.
   */
  public function hasRole($role_id);

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
   *   TRUE if the OG membership is active, FALSE otherwise.
   */
  public function isActive();

  /**
   * Returns TRUE if the OG membership is pending.
   *
   * @return bool
   *   TRUE if the OG membership is pending, FALSE otherwise.
   */
  public function isPending();

  /**
   * Returns TRUE if the OG membership is blocked.
   *
   * @return bool
   *   TRUE if the OG membership is blocked, FALSE otherwise.
   */
  public function isBlocked();

  /**
   * Returns TRUE if the OG membership belongs to the group owner.
   *
   * @return bool
   *   TRUE if the OG membership belongs to the group owner, FALSE otherwise.
   */
  public function isOwner();

}
