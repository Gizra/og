<?php

/**
 * @file
 * Contains \Drupal\og\Og.
 */

namespace Drupal\og;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\Plugin\EntityReferenceSelection\OgSelection;

/**
 * A static helper class for OG.
 */
class Og {

  /**
   * Static cache for groups per entity.
   *
   * @var array
   */
  protected static $entityGroupCache = [];

  /**
   * Create an organic groups field in a bundle.
   *
   * @param string $plugin_id
   *   The OG field plugin ID, which is also the default field name.
   * @param $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param array $settings
   *   (Optional) allow overriding the default definitions of the field storage
   *   config and field config.
   *   Allowed values:
   *   - field_storage_config: Array with values to override the field storage
   *     config definitions. Values should comply with FieldStorageConfig::create()
   *   - field_config: Array with values to override the field config
   *     definitions. Values should comply with FieldConfig::create()
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The created or existing field config.
   */
  public static function createField($plugin_id, $entity_type, $bundle, array $settings = []) {
    $settings = $settings + [
      'field_storage_config' => [],
      'field_config' => [],
    ];

    $field_name = !empty($settings['field_name']) ? $settings['field_name'] : $plugin_id;

    // Get the field definition and add the entity info to it. By doing so
    // we validate the the field can be attached to the entity. For example,
    // the OG accesss module's field can be attached only to node entities, so
    // any other entity will throw an exception.
    /** @var \Drupal\og\OgFieldBase $og_field */
    $og_field = static::getFieldBaseDefinition($plugin_id)
      ->setFieldName($field_name)
      ->setBundle($bundle)
      ->setEntityType($entity_type);

    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      $field_storage_config = NestedArray::mergeDeep($og_field->getFieldStorageConfigBaseDefinition(), $settings['field_storage_config']);
      FieldStorageConfig::create($field_storage_config)->save();
    }


    if (!$field_definition = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      $field_config = NestedArray::mergeDeep($og_field->getFieldConfigBaseDefinition(), $settings['field_config']);

      $field_definition = FieldConfig::create($field_config);
      $field_definition->save();

      // @todo: Verify this is still needed here.
      static::invalidateCache();
    }

    return $field_definition;
  }

  /**
   * Gets the groups an entity is associated with.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get groups for.
   * @param $states
   *   (optional) Array with the state to return. Defaults to active.
   * @param $field_name
   *   (optional) The field name associated with the group.
   *
   * @return array
   *  An array with the group's entity type as the key, and array - keyed by
   *  the OG membership ID and the group ID as the value. If nothing found,
   *  then an empty array.
   */
  public static function getEntityGroups(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE], $field_name = NULL) {
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    // Get a string identifier of the states, so we can retrieve it from cache.
    if ($states) {
      sort($states);
      $state_identifier = implode(':', $states);
    }
    else {
      $state_identifier = FALSE;
    }

    $identifier = [
      $entity_type_id,
      $entity_id,
      $state_identifier,
      $field_name,
    ];

    $identifier = implode(':', $identifier);
    if (isset(static::$entityGroupCache[$identifier])) {
      // Return cached values.
      return static::$entityGroupCache[$identifier];
    }

    static::$entityGroupCache[$identifier] = [];
    $query = \Drupal::entityQuery('og_membership')
      ->condition('member_entity_type', $entity_type_id)
      ->condition('member_entity_id', $entity_id);

    if ($states) {
      $query->condition('state', $states, 'IN');
    }

    if ($field_name) {
      $query->condition('field_name', $field_name);
    }

    $results = $query->execute();

    /** @var \Drupal\og\Entity\OgMembership[] $memberships */
    $memberships = \Drupal::entityTypeManager()
      ->getStorage('og_membership')
      ->loadMultiple($results);

    /** @var \Drupal\og\Entity\OgMembership $membership */
    foreach ($memberships as $membership) {
      static::$entityGroupCache[$identifier][$membership->getGroupEntityType()][$membership->id()] = $membership->getGroup();
    }

    return static::$entityGroupCache[$identifier];
  }

  /**
   * Return whether a group content belongs to a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to test the membership for.
   * @param array $states
   *   (optional) Array with the membership states to check the membership.
   *   Defaults to active memberships.
   *
   * @return bool
   *   TRUE if the entity (e.g. the user or node) belongs to a group with
   *   a certain state.
   */
  public static function isMember(EntityInterface $group, EntityInterface $entity, $states = [OgMembershipInterface::STATE_ACTIVE]) {
    $groups = static::getEntityGroups($entity, $states);
    $group_entity_type_id = $group->getEntityTypeId();
    // We need to create a map of the group ids as Og::getEntityGroups returns a
    // map of membership_id => group entity for each type.
    return !empty($groups[$group_entity_type_id]) && in_array($group->id(), array_map(function($group_entity) {
      return $group_entity->id();
    }, $groups[$group_entity_type_id]));
  }

  /**
   * Returns whether an entity belongs to a group with a pending status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return bool
   *   True if the membership is pending.
   *
   * @see \Drupal\og\Og::isMember
   */
  public static function isMemberPending(EntityInterface $group, EntityInterface $entity) {
    return static::isMember($group, $entity, [OgMembershipInterface::STATE_PENDING]);
  }

  /**
   * Returns whether an entity belongs to a group with a blocked status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to test the membership for.
   *
   * @return bool
   *   True if the membership is blocked.
   *
   * @see \Drupal\og\Og::isMember
   */
  public static function isMemberBlocked(EntityInterface $group, EntityInterface $entity) {
    return static::isMember($group, $entity, [OgMembershipInterface::STATE_BLOCKED]);
  }

  /**
   * Check if the given entity type and bundle is a group.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   *
   * @return bool
   *   True or false if the given entity is group.
   */
  public static function isGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->isGroup($entity_type_id, $bundle_id);
  }

  /**
   * Check if the given entity type and bundle is a group content.
   *
   * This is just a convenience wrapper around Og::getAllGroupAudienceFields().
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   *
   * @return bool
   *   True or false if the given entity is group content.
   */
  public static function isGroupContent($entity_type_id, $bundle_id) {
    return (bool) static::getAllGroupAudienceFields($entity_type_id, $bundle_id);
  }

  /**
   * Sets an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   *
   * @return bool
   *   True or false if the action succeeded.
   */
  public static function addGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->addGroup($entity_type_id, $bundle_id);
  }

  /**
   * Removes an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   *
   * @return bool
   *   True or false if the action succeeded.
   */
  public static function removeGroup($entity_type_id, $bundle_id) {
    return static::groupManager()->removeGroup($entity_type_id, $bundle_id);
  }

  /**
   * Return TRUE if field is a group audience type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition object.
   *
   * @return bool
   *   TRUE if the field is a group audience type, FALSE otherwise.
   */
  public static function isGroupAudienceField(FieldDefinitionInterface $field_definition) {
    return $field_definition->getType() === 'og_membership_reference';
  }

  /**
   * Return all the group audience fields of a certain bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string  $bundle
   *   The bundle name to be checked.
   * @param string $group_type_id
   *   Filter list to only include fields referencing a specific group type.
   * @param string $group_bundle
   *   Filter list to only include fields referencing a specific group bundle.
   *   Fields that do not specify any bundle restrictions at all are also
   *   included.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name; Or an empty array if
   *   none found.
   */
  public static function getAllGroupAudienceFields($entity_type_id, $bundle, $group_type_id = NULL, $group_bundle = NULL) {
    $return = [];

    foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle) as $field_definition) {
      if (!static::isGroupAudienceField($field_definition)) {
        // Not a group audience field.
        continue;
      }

      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');

      if (isset($group_type_id) && $target_type != $group_type_id) {
        // Field doesn't reference this group type.
        continue;
      }

      $handler_settings = $field_definition->getSetting('handler_settings');

      if (isset($group_bundle) && !empty($handler_settings['target_bundles']) && !in_array($group_bundle, $handler_settings['target_bundles'])) {
        continue;
      }

      $field_name = $field_definition->getName();
      $return[$field_name] = $field_definition;
    }

    return $return;
  }

  /**
   * Returns the group manager instance.
   *
   * @return \Drupal\og\GroupManager
   */
  public static function groupManager() {
    // @todo store static reference for this?
    return \Drupal::service('og.group.manager');
  }

  /**
   * Return the og permission handler instance.
   *
   * @return \Drupal\og\OgPermissionHandler;
   */
  public static function permissionHandler() {
    return \Drupal::service('og.permissions');
  }

  /**
   * Invalidate cache.
   *
   * @param $group_ids
   *   Array with group IDs that their cache should be invalidated.
   */
  public static function invalidateCache($group_ids = array()) {
    // @todo We should not be using drupal_static() review and remove.
    // Reset static cache.
    $caches = array(
      'og_user_access',
      'og_user_access_alter',
      'og_role_permissions',
      'og_get_user_roles',
      'og_get_permissions',
      'og_get_entity_groups',
      'og_get_membership',
      'og_get_field_og_membership_properties',
      'og_get_user_roles',
    );

    foreach ($caches as $cache) {
      drupal_static_reset($cache);
    }

    // @todo Consider using a reset() method.
    static::$entityGroupCache = [];

    // Invalidate the entity property cache.
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Let other OG modules know we invalidate cache.
    \Drupal::moduleHandler()->invokeAll('og_invalidate_cache', $group_ids);
  }

  /**
   * Gets the storage manage for the OG membership entity.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  public static function membershipStorage() {
    return \Drupal::entityTypeManager()->getStorage('og_membership');
  }

  /**
   * Gets the default constructor parameters for OG membership.
   */
  public static function membershipDefault() {
    return ['type' => OgMembershipInterface::TYPE_DEFAULT];
  }


  /**
   * Get an OG field base definition.
   *
   * @param string $plugin_id
   *   The plugin ID, which is also the default field name.
   *
   * @throws \Exception
   * @return OgFieldBase|bool
   *   An array with the field storage config and field config definitions, or
   *   FALSE if none found.
   */
  protected static function getFieldBaseDefinition($plugin_id) {
    /** @var OgFieldsPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.og.fields');
    if (!$field_config = $plugin_manager->getDefinition($plugin_id)) {
      throw new \Exception("The Organic Groups field with plugin ID $plugin_id is not a valid plugin.");
    }

    return $plugin_manager->createInstance($plugin_id);
  }

  /**
   * Get the selection handler for an audience field attached to entity.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $options
   *   Overriding the default options of the selection handler.
   *
   * @return OgSelection
   * @throws \Exception
   */
  public static function getSelectionHandler(FieldDefinitionInterface $field_definition, array $options = []) {
    if (!static::isGroupAudienceField($field_definition)) {
      $field_name = $field_definition->getName();
      throw new \Exception("The field $field_name is not an audience field.");
    }

    $options = NestedArray::mergeDeep([
      'target_type' => $field_definition->getFieldStorageDefinition()->getSetting('target_type'),
      'handler' => $field_definition->getSetting('handler'),
      'handler_settings' => [
        'field_mode' => 'default',
      ],
    ], $options);

    // Deep merge the handler settings.
    $options['handler_settings'] = NestedArray::mergeDeep($field_definition->getSetting('handler_settings'), $options['handler_settings']);

    return \Drupal::service('plugin.manager.entity_reference_selection')->createInstance('og:default', $options);
  }

}
