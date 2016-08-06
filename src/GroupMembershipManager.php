<?php

namespace Drupal\og;

use \Drupal\og\GroupMembershipManagerInterface;


class GroupMembershipManager implements GroupMembershipManagerInterface {

  /**
   * Returns all group IDs associated with the given user.
   *
   * This is similar to \Drupal\og\Og::getGroupIds() but for users. The reason
   * there is a separate method for user entities is because the storage is
   * handled differently. For group content the relation to the group is stored
   * on a field attached to the content entity, while user memberships are
   * tracked in OgMembership entities.
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
   * @see \Drupal\og\Og::getGroupIds()
   */
  public function getUserGroupIds(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $group_ids = [];

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = $this->getMemberships($user, $states);
    foreach ($memberships as $membership) {
      $group_ids[$membership->getGroupEntityType()][] = $membership->getGroupId();
    }

    return $group_ids;
  }

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
   * @see \Drupal\og\Og::getGroups()
   * @see \Drupal\og\Og::getMemberships()
   */
  public function getUserGroups(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $groups = [];

    foreach ($this->getUserGroupIds($user, $states) as $entity_type => $entity_ids) {
      $groups[$entity_type] = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    return $groups;
  }

  /**
   * Returns the group memberships a user is associated with.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get groups for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return \Drupal\og\Entity\OgMembership[]
   *   An array of OgMembership entities, keyed by ID.
   */
  public function getMemberships(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    // Get a string identifier of the states, so we can retrieve it from cache.
    sort($states);
    $states_identifier = implode('|', array_unique($states));

    $identifier = [
      __METHOD__,
      $user->id(),
      $states_identifier,
    ];
    $identifier = implode(':', $identifier);

    // Return cached result if it exists.
    if (isset($this->$cache[$identifier])) {
      return $this->$cache[$identifier];
    }

    $query = \Drupal::entityQuery('og_membership')
      ->condition('uid', $user->id());

    if ($states) {
      $query->condition('state', $states, 'IN');
    }

    $results = $query->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $this->$cache[$identifier] = \Drupal::entityTypeManager()
      ->getStorage('og_membership')
      ->loadMultiple($results);

    return $this->$cache[$identifier];
  }

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
   * @return \Drupal\og\Entity\OgMembership|null
   *   The OgMembership entity. NULL will be returned if no membership is
   *   available that matches the passed in $states.
   */
  public function getMembership(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    foreach ($this->getMemberships($user, $states) as $membership) {
      if ($membership->getGroupEntityType() === $group->getEntityTypeId() && $membership->getGroupId() === $group->id()) {
        return $membership;
      }
    }

    // No membership matches the request.
    return NULL;
  }

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
   * @return \Drupal\og\Entity\OgMembership
   *   The unsaved membership object.
   */
  public function createMembership(EntityInterface $group, AccountInterface $user, $membership_type = OgMembershipInterface::TYPE_DEFAULT) {
    /** @var OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => $membership_type]);
    $membership
      ->setUser($user)
      ->setGroup($group);

    return $membership;
  }

  /**
   * Returns all group IDs associated with the given group content entity.
   *
   * Do not use this to retrieve group IDs associated with a user entity. Use
   * Og::getUserGroups() instead.
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
   * @see \Drupal\og\Og::getUserGroups()
   */
  public function getGroupIds(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    // This does not work for user entities.
    if ($entity->getEntityTypeId() === 'user') {
      throw new \InvalidArgumentException('\Drupal\og\Og::getGroupIds() cannot be used for user entities. Use \Drupal\og\Og::getUserGroups() instead.');
    }

    $identifier = [
      __METHOD__,
      $entity->id(),
      $group_type_id,
      $group_bundle,
    ];

    $identifier = implode(':', $identifier);

    if (isset($this->$cache[$identifier])) {
      // Return cached values.
      return $this->$cache[$identifier];
    }

    $group_ids = [];

    $fields = OgGroupAudienceHelper::getAllGroupAudienceFields($entity->getEntityTypeId(), $entity->bundle(), $group_type_id, $group_bundle);
    foreach ($fields as $field) {
      $target_type = $field->getFieldStorageDefinition()->getSetting('target_type');

      // Optionally filter by group type.
      if (!empty($group_type_id) && $group_type_id !== $target_type) {
        continue;
      }

      // Compile a list of group target IDs.
      $target_ids = array_map(function ($value) {
        return $value['target_id'];
      }, $entity->get($field->getName())->getValue());

      if (empty($target_ids)) {
        continue;
      }

      // Query the database to get the actual list of groups. The target IDs may
      // contain groups that no longer exist. Entity reference doesn't clean up
      // orphaned target IDs.
      $entity_type = \Drupal::entityTypeManager()->getDefinition($target_type);
      $query = \Drupal::entityQuery($target_type)
        ->condition($entity_type->getKey('id'), $target_ids, 'IN');

      // Optionally filter by group bundle.
      if (!empty($group_bundle)) {
        $query->condition($entity_type->getKey('bundle'), $group_bundle);
      }

      $group_ids = NestedArray::mergeDeep($group_ids, [$target_type => $query->execute()]);
    }

    $this->$cache[$identifier] = $group_ids;

    return $group_ids;
  }

  /**
   * Returns all groups that are associated with the given group content entity.
   *
   * Do not use this to retrieve group memberships for a user entity. Use
   * Og::getUserGroups() instead.
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
   * @see \Drupal\og\Og::getUserGroups()
   */
  public function getGroups(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    $groups = [];

    foreach ($this->getGroupIds($entity, $group_type_id, $group_bundle) as $entity_type => $entity_ids) {
      $groups[$entity_type] = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    return $groups;
  }

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
  public function getGroupCount(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    return array_reduce($this->getGroupIds($entity, $group_type_id, $group_bundle), function ($carry, $item) {
      return $carry + count($item);
    }, 0);
  }

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
  public function getGroupContentIds(EntityInterface $entity, array $entity_types = []) {
    $group_content = [];

    // Retrieve the fields which reference our entity type and bundle.
    $query = \Drupal::entityQuery('field_storage_config')
      ->condition('type', OgGroupAudienceHelper::GROUP_REFERENCE);

    // Optionally filter group content entity types.
    if ($entity_types) {
      $query->condition('entity_type', $entity_types, 'IN');
    }

    /** @var \Drupal\field\FieldStorageConfigInterface[] $fields */
    $fields = array_filter(FieldStorageConfig::loadMultiple($query->execute()), function (FieldStorageConfigInterface $field) use ($entity) {
      $type_matches = $field->getSetting('target_type') === $entity->getEntityTypeId();
      // If the list of target bundles is empty, it targets all bundles.
      $bundle_matches = empty($field->getSetting('target_bundles')) || in_array($entity->bundle(), $field->getSetting('target_bundles'));
      return $type_matches && $bundle_matches;
    });

    // Compile the group content.
    foreach ($fields as $field) {
      $group_content_entity_type = $field->getTargetEntityTypeId();

      // Group the group content per entity type.
      if (!isset($group_content[$group_content_entity_type])) {
        $group_content[$group_content_entity_type] = [];
      }

      // Query all group content that references the group through this field.
      $results = \Drupal::entityQuery($group_content_entity_type)
        ->condition($field->getName() . '.target_id', $entity->id())
        ->execute();

      $group_content[$group_content_entity_type] = array_merge($group_content[$group_content_entity_type], $results);
    }

    return $group_content;
  }

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
  public function isMember(EntityInterface $group, AccountInterface $user, $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $group_ids = $this->getUserGroupIds($user, $states);
    $entity_type_id = $group->getEntityTypeId();
    return !empty($group_ids[$entity_type_id]) && in_array($group->id(), $group_ids[$entity_type_id]);
  }

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
  public function isMemberPending(EntityInterface $group, AccountInterface $user) {
    return $this->isMember($group, $user, [OgMembershipInterface::STATE_PENDING]);
  }

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
  public function isMemberBlocked(EntityInterface $group, AccountInterface $user) {
    return $this->isMember($group, $user, [OgMembershipInterface::STATE_BLOCKED]);
  }


}