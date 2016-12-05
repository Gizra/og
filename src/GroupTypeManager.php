<?php

namespace Drupal\og;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\State\StateInterface;
use Drupal\og\Event\GroupCreationEvent;
use Drupal\og\Event\GroupCreationEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A manager to keep track of which entity type/bundles are OG group enabled.
 */
class GroupTypeManager {

  /**
   * The key used to identify the cached version of the group relation map.
   */
  const GROUP_RELATION_MAP_CACHE_KEY = 'og.group_manager.group_relation_map';

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

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
   * @var array
   *   An associative array representing group and group content relations.
   *
   * This mapping is in the following format:
   * @code
   *   [
   *     'group_entity_type_id' => [
   *       'group_bundle_id' => [
   *         'group_content_entity_type_id' => [
   *           'group_content_bundle_id',
   *         ],
   *       ],
   *     ],
   *   ]
   * @endcode
   */
  protected $groupRelationMap = [];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The OG role manager.
   *
   * @var \Drupal\og\OgRoleManagerInterface
   */
  protected $ogRoleManager;

  /**
   * The route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * Constructs a GroupTypeManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   * @param EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission manager.
   * @param \Drupal\og\OgRoleManagerInterface $og_role_manager
   *   The OG role manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder service.
   * @param \Drupal\og\OgGroupAudienceHelperInterface $group_audience_helper
   *   The OG group audience helper.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EventDispatcherInterface $event_dispatcher, StateInterface $state, PermissionManagerInterface $permission_manager, OgRoleManagerInterface $og_role_manager, RouteBuilderInterface $route_builder, OgGroupAudienceHelperInterface $group_audience_helper) {
    $this->configFactory = $config_factory;
    $this->ogRoleStorage = $entity_type_manager->getStorage('og_role');
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->eventDispatcher = $event_dispatcher;
    $this->state = $state;
    $this->permissionManager = $permission_manager;
    $this->ogRoleManager = $og_role_manager;
    $this->routeBuilder = $route_builder;
    $this->groupAudienceHelper = $group_audience_helper;
  }

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
  public function isGroup($entity_type_id, $bundle) {
    $group_map = $this->getGroupMap();
    return isset($group_map[$entity_type_id]) && in_array($bundle, $group_map[$entity_type_id]);
  }

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
  public function isGroupContent($entity_type_id, $bundle) {
    return $this->groupAudienceHelper->hasGroupAudienceField($entity_type_id, $bundle);
  }

  /**
   * Returns the group of an entity type.
   *
   * @param string $entity_type_id
   *   The entity type name.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of groups, or an empty array if none found
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
  public function getAllGroupContentBundles() {
    $bundles = [];
    foreach ($this->getGroupRelationMap() as $group_entity_type_id => $group_bundle_ids) {
      foreach ($group_bundle_ids as $group_content_entity_type_ids) {
        foreach ($group_content_entity_type_ids as $group_content_entity_type_id => $group_content_bundle_ids) {
          $bundles[$group_content_entity_type_id] = array_merge(isset($bundles[$group_content_entity_type_id]) ? $bundles[$group_content_entity_type_id] : [], $group_content_bundle_ids);
        }
      }
    }
    return $bundles;
  }

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
  public function getAllGroupContentBundlesByEntityType($entity_type_id) {
    $bundles = $this->getAllGroupContentBundles();
    if (!isset($bundles[$entity_type_id])) {
      throw new \InvalidArgumentException("The '$entity_type_id' entity type has no group content bundles.");
    }
    return $bundles[$entity_type_id];
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

    foreach ($this->groupAudienceHelper->getAllGroupAudienceFields($group_content_entity_type_id, $group_content_bundle_id) as $field) {
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

    // Trigger an event upon the new group creation.
    $event = new GroupCreationEvent($entity_type_id, $bundle_id);
    $this->eventDispatcher->dispatch(GroupCreationEventInterface::EVENT_NAME, $event);

    $this->ogRoleManager->createPerBundleRoles($entity_type_id, $bundle_id);
    $this->refreshGroupMap();

    // Routes will need to be rebuilt.
    $this->routeBuilder->setRebuildNeeded();
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
      $editable->save();

      // Remove all roles associated with this group type.
      $this->ogRoleManager->removeRoles($entity_type_id, $bundle_id);

      $this->resetGroupMap();

      // Routes will need to be rebuilt.
      $this->routeBuilder->setRebuildNeeded();
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
    $this->state->delete(self::GROUP_RELATION_MAP_CACHE_KEY);
  }

  /**
   * Returns the group map.
   *
   * @return array
   *   The group map.
   */
  public function getGroupMap() {
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
   * Populates the map of relations between group types and group content types.
   */
  protected function refreshGroupRelationMap() {
    // Retrieve a cached version of the map if it exists.
    if ($group_relation_map = $this->state->get(self::GROUP_RELATION_MAP_CACHE_KEY)) {
      $this->groupRelationMap = $group_relation_map;
      return;
    }

    $this->groupRelationMap = [];

    $user_bundles = \Drupal::entityTypeManager()->getDefinition('user')->getKey('bundle') ?: ['user'];

    foreach ($this->entityTypeBundleInfo->getAllBundleInfo() as $group_content_entity_type_id => $bundles) {
      foreach ($bundles as $group_content_bundle_id => $bundle_info) {

        if (in_array($group_content_bundle_id, $user_bundles)) {
          // User is not a group content per se. Remove it.
          continue;
        }

        foreach ($this->getGroupBundleIdsByGroupContentBundle($group_content_entity_type_id, $group_content_bundle_id) as $group_entity_type_id => $group_bundle_ids) {
          foreach ($group_bundle_ids as $group_bundle_id) {
            $this->groupRelationMap[$group_entity_type_id][$group_bundle_id][$group_content_entity_type_id][$group_content_bundle_id] = $group_content_bundle_id;
          }
        }
      }
    }
    // Cache the map.
    $this->state->set(self::GROUP_RELATION_MAP_CACHE_KEY, $this->groupRelationMap);
  }

}
