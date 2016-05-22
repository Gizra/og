<?php

/**
 * @file
 * Contains \Drupal\og\GroupManager.
 */

namespace Drupal\og;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * A manager to keep track of which entity type/bundles are OG group enabled.
 */
class GroupManager {

  /**
   * The OG settings configuration key.
   *
   * @var string
   */
  const SETTINGS_CONFIG_KEY = 'og.settings';

  /**
   * The OG group settings config key.
   *
   * @var string
   */
  const GROUPS_CONFIG_KEY = 'groups';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The service providing information about bundles.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * A map of entity types and bundles.
   *
   * Do not access this property directly, use $this->getGroupMap() instead.
   *
   * @var array
   */
  protected $groupMap;

  /**
   * A map of group and group content relations.
   *
   * Do not access this property directly, use $this->getGroupRelationMap()
   * instead.
   *
   * @var array $groupRelationMap
   *   An associative array representing group and group content relations, in
   *   the following format:
   *   @code
   *   [
   *     'group_entity_type_id' => [
   *       'group_bundle_id' => [
   *         'group_content_entity_type_id' => [
   *           'group_content_bundle_id',
   *         ],
   *       ],
   *     ],
   *   ]
   *   @endcode
   */
  protected $groupRelationMap = [];

  /**
   * Constructs an GroupManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->configFactory = $config_factory;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Determines whether an entity type ID and bundle ID are group enabled.
   *
   * @param string $entity_type_id
   * @param string $bundle
   *
   * @return bool
   */
  public function isGroup($entity_type_id, $bundle) {
    $group_map = $this->getGroupMap();
    return isset($group_map[$entity_type_id]) && in_array($bundle, $group_map[$entity_type_id]);
  }

  /**
   * @param $entity_type_id
   *
   * @return array
   */
  public function getGroupsForEntityType($entity_type_id) {
    $group_map = $this->getGroupMap();
    return isset($group_map[$entity_type_id]) ? $group_map[$entity_type_id] : [];
  }

  /**
   * Get all group bundles keyed by entity type.
   *
   * @return array
   *   An associative array, keyed by entity type, each value an indexed array
   *   of bundle IDs.
   */
  public function getAllGroupBundles($entity_type = NULL) {
    $group_map = $this->getGroupMap();
    return !empty($group_map[$entity_type]) ? $group_map[$entity_type] : $group_map;
  }

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
  public function getGroupBundleIdsByGroupContentBundle($group_content_entity_type_id, $group_content_bundle_id) {
    $bundles = [];

    foreach (OgGroupAudienceHelper::getAllGroupAudienceFields($group_content_entity_type_id, $group_content_bundle_id) as $field) {
      $group_entity_type_id = $field->getSetting('target_type');
      $handler_settings = $field->getSetting('handler_settings');
      $group_bundle_ids = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : [];

      // If the group bundles are empty, it means that all bundles are
      // referenced.
      if (empty($group_bundle_ids)) {
        $group_bundle_ids = $this->getGroupMap()[$group_entity_type_id];
      }

      foreach ($group_bundle_ids as $group_bundle_id) {
        $bundles[$group_entity_type_id][$group_bundle_id] = $group_bundle_id;
      }
    }

    return $bundles;
  }

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
  public function getGroupContentBundleIdsByGroupBundle($group_entity_type_id, $group_bundle_id) {
    $group_relation_map = $this->getGroupRelationMap();
    return isset($group_relation_map[$group_entity_type_id][$group_bundle_id]) ? $group_relation_map[$group_entity_type_id][$group_bundle_id] : [];
  }

  /**
   * Sets an entity type instance as being an OG group.
   */
  public function addGroup($entity_type_id, $bundle_id) {
    $editable = $this->configFactory->getEditable('og.settings');
    $groups = $editable->get('groups');
    $groups[$entity_type_id][] = $bundle_id;
    // @todo, just key by bundle ID instead?
    $groups[$entity_type_id] = array_unique($groups[$entity_type_id]);

    $editable->set('groups', $groups);
    $saved = $editable->save();

    $this->refreshGroupMap();

    return $saved;
  }

  /**
   * Removes an entity type instance as being an OG group.
   */
  public function removeGroup($entity_type_id, $bundle_id) {
    $editable = $this->configFactory->getEditable('og.settings');
    $groups = $editable->get('groups');

    if (isset($groups[$entity_type_id])) {
      $search_key = array_search($bundle_id, $groups[$entity_type_id]);

      if ($search_key !== FALSE) {
        unset($groups[$entity_type_id][$search_key]);
      }

      // Clean up entity types that have become empty.
      $groups = array_filter($groups);

      // Only update and refresh the map if a key was found and unset.
      $editable->set('groups', $groups);
      $saved = $editable->save();

      $this->resetGroupMap();

      return $saved;
    }
  }

  /**
   * Resets all locally stored data.
   */
  public function reset() {
    $this->resetGroupMap();
    $this->resetGroupRelationMap();
  }

  /**
   * Resets the cached group map.
   *
   * Call this after adding or removing a group type.
   */
  public function resetGroupMap() {
    $this->groupMap = [];
  }

  /**
   * Resets the cached group relation map.
   *
   * Call this after making a change to the relationship between a group type
   * and a group content type.
   */
  public function resetGroupRelationMap() {
    $this->groupRelationMap = [];
  }

  /**
   * Returns the group map.
   *
   * @return array
   *   The group map.
   */
  protected function getGroupMap() {
    if (empty($this->groupMap)) {
      $this->refreshGroupMap();
    }
    return $this->groupMap;
  }

  /**
   * Returns the group relation map.
   *
   * @return array
   *   The group relation map.
   */
  protected function getGroupRelationMap() {
    if (empty($this->groupRelationMap)) {
      $this->refreshGroupRelationMap();
    }
    return $this->groupRelationMap;
  }

  /**
   * Refreshes the groupMap property with currently configured groups.
   */
  protected function refreshGroupMap() {
    $group_map = $this->configFactory->get(static::SETTINGS_CONFIG_KEY)->get(static::GROUPS_CONFIG_KEY);
    $this->groupMap = !empty($group_map) ? $group_map : [];
  }

  /**
   * Builds the map of relations between group types and group content types.
   */
  protected function refreshGroupRelationMap() {
    $this->groupRelationMap = [];

    foreach ($this->entityTypeBundleInfo->getAllBundleInfo() as $group_content_entity_type_id => $bundles) {
      foreach ($bundles as $group_content_bundle_id => $bundle_info) {
        foreach ($this->getGroupBundleIdsByGroupContentBundle($group_content_entity_type_id, $group_content_bundle_id) as $group_entity_type_id => $group_bundle_ids) {
          foreach ($group_bundle_ids as $group_bundle_id) {
            $this->groupRelationMap[$group_entity_type_id][$group_bundle_id][$group_content_entity_type_id][$group_content_bundle_id] = $group_content_bundle_id;
          }
        }
      }
    }
  }

}
