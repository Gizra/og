<?php

/**
 * @file
 * Contains \Drupal\og\GroupManager.
 */

namespace Drupal\og;

use Drupal\Core\Config\ConfigFactoryInterface;

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
   * A map of entity types and bundles.
   *
   * @var array
   */
  protected $groupMap;

  /**
   * Constructs an GroupManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->refreshGroupMap();
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
   */
  public function getAllGroupBundles($entity_type = NULL) {
    return !empty($this->groupMap[$entity_type]) ? $this->groupMap[$entity_type] : $this->groupMap;
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

      // Only update and refresh the map if a key was found and unset.
      $editable->set('groups', $groups);
      $saved = $editable->save();

      $this->refreshGroupMap();

      return $saved;
    }
  }

  /**
   * Refreshes the groupMap property with currently configured groups.
   */
  protected function refreshGroupMap() {
    $this->groupMap = $this->configFactory->get(static::SETTINGS_CONFIG_KEY)->get(static::GROUPS_CONFIG_KEY);
  }

}
