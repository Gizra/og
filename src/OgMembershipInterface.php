<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
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
   * An array containing all possible group membership states.
   */
  const ALL_STATES = [
    self::STATE_ACTIVE,
    self::STATE_PENDING,
    self::STATE_BLOCKED,
  ];

  /**
   * The default group membership type that is the bundle of group membership.
   */
  const TYPE_DEFAULT = 'default';

  /**
   * The name of the user's request field in the default group membership type.
   */
  const REQUEST_FIELD = 'og_membership_request';

  /**
   * The prefix that is used to identify group membership list cache tags.
   */
  const GROUP_MEMBERSHIP_LIST_CACHE_TAG_PREFIX = 'og-group-membership-list';

  /**
   * Gets the membership creation timestamp.
   *
   * @return int
   *   The membership creation timestamp.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the membership creation timestamp.
   *
   * @param int $timestamp
   *   The membership creation timestamp.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setCreatedTime(int $timestamp): OgMembershipInterface;

  /**
   * Sets the group associated with the membership.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The entity object.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setGroup(ContentEntityInterface $group): OgMembershipInterface;

  /**
   * Gets the group associated with the membership.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The group object which is referenced by the membership, or NULL if the
   *   group no longer exists in the entity storage. This can happen when the
   *   cleanup of orphaned memberships is configured to be handled in a cron job
   *   or batch process.
   */
  public function getGroup(): ?ContentEntityInterface;

  /**
   * Gets the group entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getGroupEntityType(): string;

  /**
   * Gets the group entity bundle.
   *
   * @return string
   *   The bundle.
   */
  public function getGroupBundle(): string;

  /**
   * Gets the group entity ID.
   *
   * @return string
   *   The entity identifier.
   */
  public function getGroupId(): string;

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
  public function setState(string $state): OgMembershipInterface;

  /**
   * Gets the membership state.
   *
   * @return string
   *   The state of the membership. It may be of the following constants:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED
   */
  public function getState(): string;

  /**
   * Gets the membership type.
   *
   * @return string
   *   The bundle of the membership type.
   */
  public function getType(): string;

  /**
   * Sets the group's roles for the current user group membership.
   *
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   The array of OG roles to set.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function setRoles(array $roles = []): OgMembershipInterface;

  /**
   * Adds a role to the user membership.
   *
   * @param \Drupal\og\OgRoleInterface $role
   *   The OG role.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function addRole(OgRoleInterface $role): OgMembershipInterface;

  /**
   * Revokes a role from the OG membership.
   *
   * @param \Drupal\og\OgRoleInterface $role
   *   The OG role.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function revokeRole(OgRoleInterface $role): OgMembershipInterface;

  /**
   * Revokes a role from the OG membership.
   *
   * @param string $role_id
   *   The OG role ID.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The updated OG Membership object.
   */
  public function revokeRoleById(string $role_id): OgMembershipInterface;

  /**
   * Gets all the referenced OG roles.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *   List of OG roles the user own for the current membership instance.
   */
  public function getRoles(): array;

  /**
   * Gets all the referenced OG role IDs.
   *
   * @return string[]
   *   List of OG role IDs that are granted in the membership.
   */
  public function getRolesIds(): array;

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
  public function isRoleValid(OgRoleInterface $role): bool;

  /**
   * Checks if the membership has the role with the given ID.
   *
   * @param string $role_id
   *   The ID of the role to check.
   *
   * @return bool
   *   True if the membership has the role.
   */
  public function hasRole(string $role_id): bool;

  /**
   * Checks if the user has a permission inside the group.
   *
   * @param string $permission
   *   The name of the permission.
   *
   * @return bool
   *   TRUE if the user has permission.
   */
  public function hasPermission(string $permission): bool;

  /**
   * Returns TRUE if the OG membership is active.
   *
   * @return bool
   *   TRUE if the OG membership is active, FALSE otherwise.
   */
  public function isActive(): bool;

  /**
   * Returns TRUE if the OG membership is pending.
   *
   * @return bool
   *   TRUE if the OG membership is pending, FALSE otherwise.
   */
  public function isPending(): bool;

  /**
   * Returns TRUE if the OG membership is blocked.
   *
   * @return bool
   *   TRUE if the OG membership is blocked, FALSE otherwise.
   */
  public function isBlocked(): bool;

  /**
   * Returns TRUE if the OG membership belongs to the group owner.
   *
   * @return bool
   *   TRUE if the OG membership belongs to the group owner, FALSE otherwise.
   */
  public function isOwner(): bool;

}
