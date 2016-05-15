<?php

/**
 * @file
 * Contains \Drupal\og\GroupManager.
 */

namespace Drupal\og;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\field\Entity\FieldConfig;

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
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A map of entity types and bundles.
   *
   * @var array
   */
  protected $groupMap;

  /**
   * Constructs an GroupManager object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
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

    // Notify other module we added a new group.
    // todo: should this be an event?
    $this->attachUserField($entity_type_id, $bundle_id);
    $this->moduleHandler->invokeAll('og_group_created', [$entity_type_id, $bundle_id]);

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

  /**
   * Attaching an audience field to the user.
   *
   * @param $entity_type_id
   *   The group entity type ID i.e: node, comment.
   * @param $bundle_id
   *   The group bundle ID i.e.: article, blog.
   */
  protected function attachUserField($entity_type_id, $bundle_id) {
    $fields = OgGroupAudienceHelper::getAllGroupAudienceFields('user', 'user');

    foreach ($fields as $field) {

      if ($field->getFieldStorageDefinition()->getSetting('target_type') == $entity_type_id) {

        if (!$field->getSetting('handler_settings')['target_bundles']) {
          return;
        }

        if (in_array($bundle_id, $field->getSetting('handler_settings')['target_bundles'])) {
          return;
        }
      }
    }

    // If we reached here, it means we need to create a field.
    // Pick an unused name.
    $field_name = substr("og_user_$entity_type_id", 0, 32);
    $i = 1;
    while (FieldConfig::loadByName($entity_type_id, $bundle_id, $field_name)) {
      $field_name = substr("og_user_$entity_type_id", 0, 32 - strlen($i)) . $i;
      ++$i;
    }

    if (!$user_bundles = \Drupal::entityTypeManager()->getDefinition('user')->getKey('bundle_id')) {
      $user_bundles = [];
    }

    $user_bundles[] = 'user';

    $settings = [
      'field_name' => $field_name,
      'field_storage_config' => [
        'settings' => [
          'target_type' => $entity_type_id,
        ],
      ],
      'field_config' => [
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [$bundle_id => $bundle_id],
          ],
        ],
      ],
    ];

    foreach ($user_bundles as $user_bundle) {
      Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', $user_bundle, $settings);
    }
  }

}
