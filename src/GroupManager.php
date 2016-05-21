<?php

/**
 * @file
 * Contains \Drupal\og\GroupManager.
 */

namespace Drupal\og;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Entity\OgRole;

/**
 * A manager to keep track of which entity type/bundles are OG group enabled.
 *
 * @property array $groupRelationMap
 *   A map of group and group content relations. This is an associative array
 *   representing group and group content relations, in the following format:
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
   * The entity storage for OgRole entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogRoleStorage;

  /**
   * The service providing information about bundles.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * A map of entity types and bundles.
   *
   * @var array
   */
  protected $groupMap;

  /**
   * Constructs an GroupManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->configFactory = $config_factory;
    $this->ogRoleStorage = $entity_type_manager->getStorage('og_role');
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->refreshGroupMap();
  }

  /**
   * Magic getter.
   *
   * @param string $property
   *   The property being gotten.
   *
   * @return mixed
   *   The property value.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid property is passed.
   */
  public function __get($property) {
    // Computing the group relation map is expensive, do it only on demand.
    if ($property === 'groupRelationMap') {
      $this->refreshGroupRelationMap();
      return $this->groupRelationMap;
    }

    throw new \InvalidArgumentException(__CLASS__ . '->' . $property . ' is undefined.');
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
    return isset($this->groupMap[$entity_type_id]) && in_array($bundle, $this->groupMap[$entity_type_id]);
  }

  /**
   * @param $entity_type_id
   *
   * @return array
   */
  public function getGroupsForEntityType($entity_type_id) {
    return isset($this->groupMap[$entity_type_id]) ? $this->groupMap[$entity_type_id] : [];
  }

  /**
   * Get all group bundles keyed by entity type.
   *
   * @return array
   *   An associative array, keyed by entity type, each value an indexed array
   *   of bundle IDs.
   */
  public function getAllGroupBundles($entity_type = NULL) {
    return !empty($this->groupMap[$entity_type]) ? $this->groupMap[$entity_type] : $this->groupMap;
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
        $group_bundle_ids = $this->groupMap[$group_entity_type_id];
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
    return isset($this->groupRelationMap[$group_entity_type_id][$group_bundle_id]) ? $this->groupRelationMap[$group_entity_type_id][$group_bundle_id] : [];
  }

  /**
   * Sets an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type ID of the bundle to declare as being a group.
   * @param string $bundle_id
   *   The bundle ID of the bundle to declare as being a group.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the given bundle is already a group.
   */
  public function addGroup($entity_type_id, $bundle_id) {
    // Throw an error if the entity type is already defined as a group.
    if ($this->isGroup($entity_type_id, $bundle_id)) {
      throw new \InvalidArgumentException("The '$entity_type_id' of type '$bundle_id' is already a group.");
    }
    $editable = $this->configFactory->getEditable('og.settings');
    $groups = $editable->get('groups');
    $groups[$entity_type_id][] = $bundle_id;
    // @todo, just key by bundle ID instead?
    $groups[$entity_type_id] = array_unique($groups[$entity_type_id]);

    $editable->set('groups', $groups);
    $editable->save();

    $this->createRoles($entity_type_id, $bundle_id);
    $this->refreshGroupMap();
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

      // Remove all roles associated with this group type.
      $this->removeRoles($entity_type_id, $bundle_id);

      $this->refreshGroupMap();

      return $saved;
    }
  }

  /**
   * Creates default roles for the given group type.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group for which to create default roles.
   * @param string $bundle_id
   *   The bundle ID of the group for which to create default roles.
   */
  protected function createRoles($entity_type_id, $bundle_id) {
    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle_id,
    ];
    foreach ([OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED, OgRoleInterface::ADMINISTRATOR] as $role_name) {
      $properties['id'] = $role_name;
      $properties['role_type'] = OgRole::getRoleTypeByName($role_name);

      $role = $this->ogRoleStorage->create($properties + OgRole::getDefaultProperties()[$role_name]);
      $role->save();
    }
  }

  /**
   * Deletes the roles associated with a group type.
   *
   * @param string $entity_type_id
   *   The entity type ID of the group for which to delete the roles.
   * @param string $bundle_id
   *   The bundle ID of the group for which to delete the roles.
   */
  protected function removeRoles($entity_type_id, $bundle_id) {
    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle_id,
    ];
    foreach ($this->ogRoleStorage->loadByProperties($properties) as $role) {
      $role->delete();
    }
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
