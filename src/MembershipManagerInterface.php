<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

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
   * @param int $user_id
   *   The ID of the user to get groups for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return array
   *   An associative array, keyed by group entity type, each item an array of
   *   group entity IDs.
   *
   * @see \Drupal\og\MembershipManager::getGroupIds()
   */
  public function getUserGroupIds($user_id, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns all groups associated with the given user.
   *
   * This is similar to \Drupal\og\Og::getGroups() but for users. The reason
   * there is a separate method for user entities is because the storage is
   * handled differently. For group content the relation to the group is stored
   * on a field attached to the content entity, while user memberships are
   * tracked in OgMembership entities.
   *
   * @param int $user_id
   *   The ID of the user to get groups for.
   * @param string[] $states
   *   (optional) Array with the states to return. Defaults to active.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[][]
   *   An associative array, keyed by group entity type, each item an array of
   *   group entities.
   *
   * @see \Drupal\og\MembershipManager::getGroups()
   * @see \Drupal\og\MembershipManager::getMemberships()
   */
  public function getUserGroups($user_id, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns an array of groups filtered by the OG roles of the user.
   *
   * @param int $user_id
   *   The ID of the user to get the groups for.
   * @param string[] $role_ids
   *   A list of OG role IDs to filter by.
   * @param string[] $states
   *   (optional) An array of states to filter the memberships by.
   * @param bool $require_all_roles
   *   (optional) If set to TRUE, all requested roles must be present to return
   *   the group. Set to FALSE to return the groups that match one or more of
   *   the requested roles. Defaults to TRUE.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[][]
   *   An associative array, keyed by group entity type, each item an array of
   *   group entities.
   */
  public function getUserGroupsByRoleIds($user_id, array $role_ids, array $states = [OgMembershipInterface::STATE_ACTIVE], bool $require_all_roles = TRUE): array;

  /**
   * Returns an array of groups ids filtered by the og roles of the user.
   *
   * @param int $user_id
   *   The ID of the user to get the groups for.
   * @param string[] $role_ids
   *   A list of OG role IDs to filter by.
   * @param string[] $states
   *   (optional) An array of states to filter the memberships by.
   * @param bool $require_all_roles
   *   (optional) If set to TRUE, all requested roles must be present to return
   *   the group. Set to FALSE to return the groups that match one or more of
   *   the requested roles. Defaults to TRUE.
   *
   * @return array[]
   *   An associative array, keyed by group entity type, each item an array of
   *   group IDs.
   */
  public function getUserGroupIdsByRoleIds($user_id, array $role_ids, array $states = [OgMembershipInterface::STATE_ACTIVE], bool $require_all_roles = TRUE): array;

  /**
   * Returns the group memberships a user is associated with.
   *
   * @param int $user_id
   *   The ID of the user to get group memberships for.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to only returning
   *   active memberships. In order to retrieve all memberships regardless of
   *   state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   An array of OgMembership entities, keyed by ID.
   */
  public function getMemberships($user_id, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the group membership for a given user and group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to get the membership for.
   * @param int $user_id
   *   The ID of the user to get the membership for.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to only returning
   *   active memberships. In order to retrieve all memberships regardless of
   *   state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The OgMembership entity. NULL will be returned if no membership is
   *   available that matches the passed in $states.
   */
  public function getMembership(EntityInterface $group, $user_id, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the membership IDs of the given group filtered by role names.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity for which to return the memberships.
   * @param array $role_names
   *   An array of role names to filter by. In order to retrieve a list of all
   *   membership IDs, pass `[OgRoleInterface::AUTHENTICATED]`.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to only returning
   *   active membership IDs. In order to retrieve all membership IDs regardless
   *   of state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The membership entities.
   */
  public function getGroupMembershipIdsByRoleNames(EntityInterface $group, array $role_names, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns the memberships of the given group filtered by role name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity for which to return the memberships.
   * @param array $role_names
   *   An array of role names to filter by. In order to retrieve a list of all
   *   memberships, pass `[OgRoleInterface::AUTHENTICATED]`.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to only returning
   *   active memberships. In order to retrieve all memberships regardless of
   *   state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The membership entities.
   */
  public function getGroupMembershipsByRoleNames(EntityInterface $group, array $role_names, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Creates an OG membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   * @param string $membership_type
   *   (optional) The membership type. Defaults to
   *   \Drupal\og\OgMembershipInterface::TYPE_DEFAULT.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The unsaved membership object.
   */
  public function createMembership(EntityInterface $group, UserInterface $user, $membership_type = OgMembershipInterface::TYPE_DEFAULT);

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
   * Use count(\Drupal\og\MembershipManager::getUserGroupIds()) instead.
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
   * @param int $user_id
   *   The ID of the user to test the membership for.
   * @param array $states
   *   (optional) Array with the membership states to check the membership.
   *   Defaults to active memberships.
   *
   * @return bool
   *   TRUE if the entity (e.g. the user or node) belongs to a group with
   *   a certain state.
   */
  public function isMember(EntityInterface $group, $user_id, array $states = [OgMembershipInterface::STATE_ACTIVE]);

  /**
   * Returns whether a user belongs to a group with a pending status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param int $user_id
   *   The ID of the user.
   *
   * @return bool
   *   True if the membership is pending.
   *
   * @see \Drupal\og\Og::isMember
   */
  public function isMemberPending(EntityInterface $group, $user_id);

  /**
   * Returns whether an entity belongs to a group with a blocked status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param int $user_id
   *   The ID of the user to test the membership for.
   *
   * @return bool
   *   True if the membership is blocked.
   *
   * @see \Drupal\og\Og::isMember
   */
  public function isMemberBlocked(EntityInterface $group, $user_id);

}
