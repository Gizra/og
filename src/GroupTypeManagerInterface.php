<?php

declare(strict_types = 1);

namespace Drupal\og;

/**
 * Interface for services intended to help managing groups.
 */
interface GroupTypeManagerInterface {

  /**
   * Determines whether an entity type ID and bundle ID are group enabled.
   *
   * @param string $entity_type_id
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   *
   * @return bool
   *   TRUE if a bundle is a group.
   */
  public function isGroup(string $entity_type_id, string $bundle): bool;

  /**
   * Checks if the given entity bundle is group content.
   *
   * This is provided as a convenient sister method to ::isGroup(). It is a
   * simple wrapper for OgGroupAudienceHelperInterface::hasGroupAudienceField().
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   *
   * @return bool
   *   TRUE if the entity bundle is group content.
   */
  public function isGroupContent(string $entity_type_id, string $bundle): bool;

  /**
   * Returns the group of an entity type.
   *
   * @param string $entity_type_id
   *   The entity type name.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of groups, or an empty array if none found
   */
  public function getGroupBundleIdsByEntityType(string $entity_type_id): array;

  /**
   * Returns a list of all group content bundles IDs keyed by entity type.
   *
   * This will return a simple list of group content bundles IDs. If you need
   * information about the relations between groups and group content bundles
   * then use getGroupRelationMap() instead.
   *
   * @return string[][]
   *   An associative array of group content bundle IDs, keyed by entity type
   *   ID.
   *
   * @see \Drupal\og\GroupTypeManagerInterface::getGroupRelationMap()
   */
  public function getAllGroupContentBundleIds(): array;

  /**
   * Returns a list of all group content bundles filtered by entity type.
   *
   * This will return a simple list of group content bundles. If you need
   * information about the relations between groups and group content bundles
   * then use getGroupRelationMap() instead.
   *
   * @param string $entity_type_id
   *   Entity type ID to filter the bundles by.
   *
   * @return string[]
   *   An array of group content bundle IDs.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed in entity type ID does not have any group content
   *   bundles defined.
   *
   * @see \Drupal\og\GroupTypeManagerInterface::getGroupRelationMap()
   */
  public function getAllGroupContentBundlesByEntityType(string $entity_type_id): array;

  /**
   * Returns all group bundles that are referenced by the given group content.
   *
   * @param string $group_content_entity_type_id
   *   The entity type ID of the group content type for which to return
   *   associated group bundle IDs.
   * @param string $group_content_bundle_id
   *   The bundle ID of the group content type for which to return associated
   *   group bundle IDs.
   *
   * @return string[][]
   *   An array of group bundle IDs, keyed by group entity type ID.
   */
  public function getGroupBundleIdsByGroupContentBundle(string $group_content_entity_type_id, string $group_content_bundle_id): array;

  /**
   * Returns group content bundles that are referencing the given group content.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group type for which to return associated group
   *   content bundle IDs.
   * @param string $group_bundle_id
   *   The bundle ID of the group type for which to return associated group
   *   content bundle IDs.
   *
   * @return string[][]
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   */
  public function getGroupContentBundleIdsByGroupBundle(string $group_entity_type_id, string $group_bundle_id): array;

  /**
   * Declares a bundle of an entity type as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type ID of the bundle to declare as being a group.
   * @param string $bundle_id
   *   The bundle ID of the bundle to declare as being a group.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the given bundle is already a group or is invalid.
   */
  public function addGroup(string $entity_type_id, string $bundle_id): void;

  /**
   * Removes an entity type instance as being an OG group.
   */
  public function removeGroup(string $entity_type_id, string $bundle_id): void;

  /**
   * Resets all locally stored data.
   */
  public function reset(): void;

  /**
   * Resets the cached group map.
   *
   * Call this after adding or removing a group type.
   */
  public function resetGroupMap(): void;

  /**
   * Resets the cached group relation map.
   *
   * Call this after making a change to the relationship between a group type
   * and a group content type.
   */
  public function resetGroupRelationMap(): void;

  /**
   * Returns the group map.
   *
   * @return string[][]
   *   The group map.
   */
  public function getGroupMap(): array;

}
