<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Membership manager interface.
 */
interface MembershipManagerInterface {

  /**
   * Returns all group IDs associated with the given user.
   *
   * This is similar to \Drupal\og\MembershipManager::getGroupIds() but
   * for users. The reason there is a separate method for user entities is
   * because the storage is handled differently. For group content the relation
   * to the group is stored on a field attached to the content entity, while
   * user memberships are tracked in OgMembership entities.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get groups for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return array
   *   An associative array, keyed by group entity type, each item an array of
   *   group entity IDs.
   *
   * @see \Drupal\og\MembershipManager::getGroupIds()
   */
  public function getUserGroupIds(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns all groups associated with the given user.
   *
   * This is similar to \Drupal\og\Og::getGroups() but for users. The reason
   * there is a separate method for user entities is because the storage is
   * handled differently. For group content the relation to the group is stored
   * on a field attached to the content entity, while user memberships are
   * tracked in OgMembership entities.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get groups for.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to active.
   *
   * @return \Drupal\Core\Entity\EntityInterface[][]
   *   An associative array, keyed by group entity type, each item an array of
   *   group entities.
   *
   * @see \Drupal\og\MembershipManager::getGroups()
   * @see \Drupal\og\MembershipManager::getMemberships()
   */
  public function getUserGroups(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the group memberships a user is associated with.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get groups for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   An array of OgMembership entities, keyed by ID.
   */
  public function getMemberships(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the group memberships for a given group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to get the membership for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   An array of OgMembership entities, keyed by ID.
   */
  public function getGroupMemberships(EntityInterface $group, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the number of group memberships for a given group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to get the membership for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return int
   *   The number of memberships for the group.
   */
  public function getGroupMembershipCount(EntityInterface $group, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the group membership for a given user and group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to get the membership for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the membership for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The OgMembership entity. NULL will be returned if no membership is
   *   available that matches the passed in $states.
   */
  public function getMembership(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Creates an OG membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param string $membership_type
   *   (optional) The membership type. Defaults to OG_MEMBERSHIP_TYPE_DEFAULT.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The unsaved membership object.
   */
  public function createMembership(EntityInterface $group, AccountInterface $user, $membership_type = OgMembershipInterface::TYPE_DEFAULT);

  /**
   * Returns all group IDs associated with the given group content entity.
   *
   * Do not use this to retrieve group IDs associated with a user entity. Use
   * MembershipManager::getUserGroups() instead.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity for which to return the associated groups.
   * @param string $group_type_id
   *   Filter results to only include group IDs of this entity type.
   * @param string $group_bundle
   *   Filter list to only include group IDs with this bundle.
   *
   * @return array
   *   An associative array, keyed by group entity type, each item an array of
   *   group entity IDs.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a user entity is passed in.
   *
   * @see \Drupal\og\GroupMembershipInterface::getUserGroups()
   */
  public function getGroupIds(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL);

  /**
   * Returns all groups that are associated with the given group content entity.
   *
   * Do not use this to retrieve group memberships for a user entity. Use
   * GroupMembershipInterface::getUserGroups() instead.
   *
   * The reason there are separate method for group content and user entities is
   * because the storage is handled differently. For group content the relation
   * to the group is stored on a field attached to the content entity, while
   * user memberships are tracked in OgMembership entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity for which to return the groups.
   * @param string $group_type_id
   *   Filter results to only include groups of this entity type.
   * @param string $group_bundle
   *   Filter results to only include groups of this bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface[][]
   *   An associative array, keyed by group entity type, each item an array of
   *   group entities.
   *
   * @see \Drupal\og\GroupMembershipInterface::getUserGroups()
   */
  public function getGroups(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL);

  /**
   * Returns the number of groups associated with a given group content entity.
   *
   * Do not use this to retrieve the group membership count for a user entity.
   * Use count(Og::GetEntityGroups()) instead.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity for which to count the associated groups.
   * @param string $group_type_id
   *   Only count groups of this entity type.
   * @param string $group_bundle
   *   Only count groups of this bundle.
   *
   * @return int
   *   The number of associated groups.
   */
  public function getGroupCount(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL);

  /**
   * Returns all the group content IDs associated with a given group entity.
   *
   * This does not return information about users that are members of the given
   * group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity for which to return group content IDs.
   * @param array $entity_types
   *   Optional list of group content entity types for which to return results.
   *   If an empty array is passed, the group content is not filtered. Defaults
   *   to an empty array.
   *
   * @return array
   *   An associative array, keyed by group content entity type, each item an
   *   array of group content entity IDs.
   */
  public function getGroupContentIds(EntityInterface $entity, array $entity_types = []);

  /**
   * Returns whether a user belongs to a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to test the membership for.
   * @param array $states
   *   (optional) Array with the membership states to check the membership.
   *   Defaults to active memberships.
   *
   * @return bool
   *   TRUE if the entity (e.g. the user or node) belongs to a group with
   *   a certain state.
   */
  public function isMember(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns whether a user belongs to a group with a pending status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   *
   * @return bool
   *   True if the membership is pending.
   *
   * @see \Drupal\og\Og::isMember
   */
  public function isMemberPending(EntityInterface $group, AccountInterface $user);

  /**
   * Returns whether an entity belongs to a group with a blocked status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The entity to test the membership for.
   *
   * @return bool
   *   True if the membership is blocked.
   *
   * @see \Drupal\og\Og::isMember
   */
  public function isMemberBlocked(EntityInterface $group, AccountInterface $user);

  /**
   * Reset the internal cache.
   */
  public function reset();

}
