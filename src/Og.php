<?php

namespace Drupal\og;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\Entity\OgRole;

/**
 * A static helper class for OG.
 */
class Og {

  /**
   * Static cache for heavy queries.
   *
   * @var array
   */
  protected static $cache = [];

  /**
   * Create an organic groups field in a bundle.
   *
   * @param string $plugin_id
   *   The OG field plugin ID, which is also the default field name.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   * @param array $settings
   *   (Optional) allow overriding the default definitions of the field storage
   *   config and field config.
   *   Allowed values:
   *   - field_storage_config: Array with values to override the field storage
   *     config definitions. Values should comply with
   *     FieldStorageConfig::create().
   *   - field_config: Array with values to override the field config
   *     definitions. Values should comply with FieldConfig::create()
   *   - form_display: Array with values to override the form display
   *     definitions.
   *   - view_display: Array with values to override the view display
   *     definitions.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The created or existing field config.
   */
  public static function createField($plugin_id, $entity_type, $bundle, array $settings = []) {
    $settings = $settings + [
      'field_storage_config' => [],
      'field_config' => [],
      'form_display' => [],
      'view_display' => [],
    ];

    $field_name = !empty($settings['field_name']) ? $settings['field_name'] : $plugin_id;

    // Get the field definition and add the entity info to it. By doing so
    // we validate the the field can be attached to the entity. For example,
    // the OG access module's field can be attached only to node entities, so
    // any other entity will throw an exception.
    /** @var \Drupal\og\OgFieldBase $og_field */
    $og_field = static::getFieldBaseDefinition($plugin_id)
      ->setFieldName($field_name)
      ->setBundle($bundle)
      ->setEntityType($entity_type);

    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      $field_storage_config = NestedArray::mergeDeep($og_field->getFieldStorageBaseDefinition(), $settings['field_storage_config']);
      FieldStorageConfig::create($field_storage_config)->save();
    }

    if (!$field_definition = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      $field_config = NestedArray::mergeDeep($og_field->getFieldBaseDefinition(), $settings['field_config']);

      $field_definition = FieldConfig::create($field_config);
      $field_definition->save();

      // @todo: Verify this is still needed here.
      static::invalidateCache();
    }

    // Make the field visible in the default form display.
    /** @var EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load("$entity_type.$bundle.default");

    // If not found, create a fresh form display object. This is by design,
    // configuration entries are only created when an entity form display is
    // explicitly configured and saved.
    if (!$form_display) {
      $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $form_display_definition = $og_field->getFormDisplayDefinition($settings['form_display']);

    $form_display->setComponent($plugin_id, $form_display_definition);
    $form_display->save();

    // Set the view display for the "default" view display.
    $view_display_definition = $og_field->getViewDisplayDefinition($settings['view_display']);

    /** @var EntityDisplayInterface $view_display */
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load("$entity_type.$bundle.default");

    if (!$view_display) {
      $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $view_display->setComponent($plugin_id, $view_display_definition);
    $view_display->save();

    // Refresh the group manager data, we have added a group type.
    static::groupTypeManager()->resetGroupRelationMap();

    return $field_definition;
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
  public static function getMemberships(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->getMemberships($user, $states);
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
  public static function getMembership(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->getMembership($group, $user, $states);
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
  public static function createMembership(EntityInterface $group, AccountInterface $user, $membership_type = OgMembershipInterface::TYPE_DEFAULT) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->createMembership($group, $user, $membership_type);
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
   *   TRUE if the user belongs to a group with a certain state.
   */
  public static function isMember(EntityInterface $group, AccountInterface $user, $states = [OgMembershipInterface::STATE_ACTIVE]) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->isMember($group, $user, $states);
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
  public static function isMemberPending(EntityInterface $group, AccountInterface $user) {
    return static::isMember($group, $user, [OgMembershipInterface::STATE_PENDING]);
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
  public static function isMemberBlocked(EntityInterface $group, AccountInterface $user) {
    return static::isMember($group, $user, [OgMembershipInterface::STATE_BLOCKED]);
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
    return static::groupTypeManager()->isGroup($entity_type_id, $bundle_id);
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
    return (bool) OgGroupAudienceHelper::getAllGroupAudienceFields($entity_type_id, $bundle_id);
  }

  /**
   * Sets an entity type instance as being an OG group.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_id
   *   The bundle name.
   */
  public static function addGroup($entity_type_id, $bundle_id) {
    static::groupTypeManager()->addGroup($entity_type_id, $bundle_id);
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
    return static::groupTypeManager()->removeGroup($entity_type_id, $bundle_id);
  }

  /**
   * Returns the group manager instance.
   *
   * @return \Drupal\og\GroupTypeManager
   *   Returns the group manager.
   */
  public static function groupTypeManager() {
    // @todo store static reference for this?
    return \Drupal::service('og.group_type_manager');
  }

  /**
   * Get a role by the group's bundle and role name.
   *
   * @param string $entity_type_id
   *   The group entity type ID.
   * @param string $bundle
   *   The group bundle name.
   * @param string $role_name
   *   The role name.
   *
   * @return \Drupal\og\OgRoleInterface|null
   *   The OG role object, or NULL if a matching role was not found.
   */
  public static function getRole($entity_type_id, $bundle, $role_name) {
    return OgRole::load($entity_type_id . '-' . $bundle . '-' . $role_name);
  }

  /**
   * Return the og permission handler instance.
   *
   * @return \Drupal\og\OgPermissionHandler
   *   Returns the OG permissions handler.
   */
  public static function permissionHandler() {
    return \Drupal::service('og.permissions');
  }

  /**
   * Invalidate cache.
   *
   * @param array $group_ids
   *   Array with group IDs that their cache should be invalidated.
   */
  public static function invalidateCache(array $group_ids = array()) {
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
    static::$cache = [];

    // Invalidate the entity property cache.
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Invalidate the group membership manager.
    \Drupal::service('og.membership_manager')->reset();

    // Let other OG modules know we invalidate cache.
    \Drupal::moduleHandler()->invokeAll('og_invalidate_cache', $group_ids);
  }

  /**
   * Get an OG field base definition.
   *
   * @param string $plugin_id
   *   The plugin ID, which is also the default field name.
   *
   * @throws \Exception
   *
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
   * @return \Drupal\og\Plugin\EntityReferenceSelection\OgSelection
   *   Returns the OG selection handler.
   *
   * @throws \Exception
   */
  public static function getSelectionHandler(FieldDefinitionInterface $field_definition, array $options = []) {
    if (!OgGroupAudienceHelper::isGroupAudienceField($field_definition)) {
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

  /**
   * Resets the static cache.
   */
  public static function reset() {
    static::$cache = [];
  }

}
