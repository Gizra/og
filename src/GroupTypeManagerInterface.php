<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Group type manager interface.
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
  public function isGroup($entity_type_id, $bundle);

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
  public function isGroupContent($entity_type_id, $bundle);

  /**
   * Returns the group of an entity type.
   *
   * @param string $entity_type_id
   *   The entity type name.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of groups, or an empty array if none found
   */
  public function getGroupsForEntityType($entity_type_id);

  /**
   * Get all group bundles keyed by entity type.
   *
   * @return array
   *   An associative array, keyed by entity type, each value an indexed array
   *   of bundle IDs.
   */
  public function getAllGroupBundles($entity_type = NULL);

  /**
   * Returns a list of all group content bundles keyed by entity type.
   *
   * This will return a simple list of group content bundles. If you need
   * information about the relations between groups and group content bundles
   * then use getGroupRelationMap() instead.
   *
   * @return array
   *   An associative array of group content bundle IDs, keyed by entity type
   *   ID.
   *
   * @see \Drupal\og\GroupTypeManager::getGroupRelationMap()
   */
  public function getAllGroupContentBundles();

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
   * @return array
   *   An array of group content bundle IDs.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed in entity type ID does not have any group content
   *   bundles defined.
   *
   * @see \Drupal\og\GroupTypeManager::getGroupRelationMap()
   */
  public function getAllGroupContentBundlesByEntityType($entity_type_id);

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
   * @return array
   *   An array of group bundle IDs, keyed by group entity type ID.
   */
  public function getGroupBundleIdsByGroupContentBundle($group_content_entity_type_id, $group_content_bundle_id);

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
   * @return array
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   */
  public function getGroupContentBundleIdsByGroupBundle($group_entity_type_id, $group_bundle_id);

  /**
   * Declares a bundle of an entity type as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type ID of the bundle to declare as being a group.
   * @param string $bundle_id
   *   The bundle ID of the bundle to declare as being a group.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the given bundle is already a group.
   */
  public function addGroup($entity_type_id, $bundle_id);

  /**
   * Removes an entity type instance as being an OG group.
   */
  public function removeGroup($entity_type_id, $bundle_id);

  /**
   * Get the membership type to use for a group.
   *
   * If there is no setting for this, falls back to 'default'.
   *
   * @param string $entity_type_id
   *   The entity type ID for the group.
   * @param string $bundle_id
   *   The bundle ID for the group.
   *
   * @return string
   *   The membership type ID to use for this group type.
   */
  public function getGroupMembershipType($entity_type_id, $bundle_id);

  /**
   * Set the membership type for a group type.
   *
   * @param string $entity_type_id
   *   The entity type ID for the group.
   * @param string $bundle_id
   *   The bundle ID for the group.
   * @param string $membership_type_id
   *   The membership type ID to use for this group type.
   */
  public function setGroupMembershipType($entity_type_id, $bundle_id, $membership_type_id);

  /**
   * Clear the membership type setting for a group type.
   *
   * @param string $entity_type_id
   *   The entity type ID for the group.
   * @param string $bundle_id
   *   The bundle ID for the group.
   */
  public function removeGroupMembershipType($entity_type_id, $bundle_id);

  /**
   * Resets all locally stored data.
   */
  public function reset();

  /**
   * Resets the cached group map.
   *
   * Call this after adding or removing a group type.
   */
  public function resetGroupMap();

  /**
   * Resets the cached group relation map.
   *
   * Call this after making a change to the relationship between a group type
   * and a group content type.
   */
  public function resetGroupRelationMap();

  /**
   * Returns the group map.
   *
   * @return array
   *   The group map.
   */
  public function getGroupMap();
  
}
