<?php

namespace Drupal\og;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\og\Entity\OgMembership;

/**
 * Membership manager.
 */
class MembershipManager implements MembershipManagerInterface {

  /**
   * Static cache of the memberships and group association.
   *
   * @var array
   */
  protected $cache;

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getUserGroups(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $groups = [];

    foreach ($this->getUserGroupIds($user, $states) as $entity_type => $entity_ids) {
      $groups[$entity_type] = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
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
    if (isset($this->cache[$identifier])) {
      return $this->cache[$identifier];
    }

    $query = \Drupal::entityQuery('og_membership')
      ->condition('uid', $user->id());

    if ($states) {
      $query->condition('state', $states, 'IN');
    }

    $results = $query->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $this->cache[$identifier] = \Drupal::entityTypeManager()
      ->getStorage('og_membership')
      ->loadMultiple($results);

    return $this->cache[$identifier];
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getGroupIds(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    // This does not work for user entities.
    if ($entity->getEntityTypeId() === 'user') {
      throw new \InvalidArgumentException('\Drupal\og\GroupMembership::getGroupIds() cannot be used for user entities. Use \Drupal\og\GroupMembership::getUserGroups() instead.');
    }

    $identifier = [
      __METHOD__,
      $entity->id(),
      $group_type_id,
      $group_bundle,
    ];

    $identifier = implode(':', $identifier);

    if (isset($this->cache[$identifier])) {
      // Return cached values.
      return $this->cache[$identifier];
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

    $this->cache[$identifier] = $group_ids;

    return $group_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    $groups = [];

    foreach ($this->getGroupIds($entity, $group_type_id, $group_bundle) as $entity_type => $entity_ids) {
      $groups[$entity_type] = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupCount(EntityInterface $entity, $group_type_id = NULL, $group_bundle = NULL) {
    return array_reduce($this->getGroupIds($entity, $group_type_id, $group_bundle), function ($carry, $item) {
      return $carry + count($item);
    }, 0);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function isMember(EntityInterface $group, AccountInterface $user, $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $group_ids = $this->getUserGroupIds($user, $states);
    $entity_type_id = $group->getEntityTypeId();
    return !empty($group_ids[$entity_type_id]) && in_array($group->id(), $group_ids[$entity_type_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function isMemberPending(EntityInterface $group, AccountInterface $user) {
    return $this->isMember($group, $user, [OgMembershipInterface::STATE_PENDING]);
  }

  /**
   * {@inheritdoc}
   */
  public function isMemberBlocked(EntityInterface $group, AccountInterface $user) {
    return $this->isMember($group, $user, [OgMembershipInterface::STATE_BLOCKED]);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->cache = [];
  }

}
