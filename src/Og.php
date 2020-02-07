<?php

namespace Drupal\og;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

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
   *   (optional) Array with the states to return. Defaults to only returning
   *   active memberships. In order to retrieve all memberships regardless of
   *   state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   An array of OgMembership entities, keyed by ID.
   */
  public static function getMemberships(AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->getMemberships($user->id(), $states);
  }

  /**
   * Returns the group membership for a given user and group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to get the membership for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the membership for.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to only returning
   *   active memberships. In order to retrieve all memberships regardless of
   *   state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The OgMembership entity. NULL will be returned if no membership is
   *   available that matches the passed in $states.
   */
  public static function getMembership(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->getMembership($group, $user->id(), $states);
  }

  /**
   * Creates an OG membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param string $membership_type
   *   (optional) The membership type. Defaults to
   *   \Drupal\og\OgMembershipInterface::TYPE_DEFAULT.
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
  public static function isMember(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->isMember($group, $user->id(), $states);
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
   * This works by checking if the bundle has one or more group audience fields.
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
    return \Drupal::service('og.group_audience_helper')->hasGroupAudienceField($entity_type_id, $bundle_id);
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
   * @return \Drupal\og\GroupTypeManagerInterface
   *   Returns the group manager.
   */
  public static function groupTypeManager() {
    // @todo store static reference for this?
    return \Drupal::service('og.group_type_manager');
  }

  /**
   * Invalidate cache.
   */
  public static function invalidateCache() {
    // @todo We should not be using drupal_static() review and remove.
    // Reset static cache.
    $caches = [
      'og_user_access',
      'og_user_access_alter',
      'og_role_permissions',
      'og_get_user_roles',
      'og_get_permissions',
      'og_get_entity_groups',
      'og_get_membership',
      'og_get_field_og_membership_properties',
      'og_get_user_roles',
    ];

    foreach ($caches as $cache) {
      drupal_static_reset($cache);
    }

    // @todo Consider using a reset() method.
    static::$cache = [];

    // Invalidate the entity property cache.
    // @todo We should not clear the entity type and field definition caches.
    // @see https://github.com/Gizra/og/issues/219
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Let other OG modules know we invalidate cache.
    \Drupal::moduleHandler()->invokeAll('og_invalidate_cache');
  }

  /**
   * Get an OG field base definition.
   *
   * @param string $plugin_id
   *   The plugin ID, which is also the default field name.
   *
   * @return OgFieldBase|bool
   *   An array with the field storage config and field config definitions, or
   *   FALSE if none found.
   *
   * @throws \Exception
   *   Thrown when the requested plugin is not valid.
   */
  protected static function getFieldBaseDefinition($plugin_id) {
    /** @var OgFieldsPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.og.fields');

    $field_config = $plugin_manager->getDefinition($plugin_id);
    if (!$field_config) {
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
   *   Thrown when the passed in field definition is not of a group audience
   *   field.
   *
   * @deprecated in og:8.x-1.0-alpha4 and is removed from og:8.x-1.0-alpha5.
   *   Use
   *   \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager::getInstance()
   *   Instead.
   * @codingStandardsIgnoreStart
   * @see https://github.com/Gizra/og/issues/580
   * @codingStandardsIgnoreEnd
   */
  public static function getSelectionHandler(FieldDefinitionInterface $field_definition, array $options = []) {
    // @codingStandardsIgnoreStart
    @trigger_error('Og:getSelectionHandler() is deprecated in og:8.x-1.0-alpha4
      and is removed from og:8.x-1.0-alpha5.
      Use \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager::getInstance()
      instead. See https://github.com/Gizra/og/issues/580', E_USER_DEPRECATED
    );
    // @codingStandardsIgnoreEnd
    if (!\Drupal::service('og.group_audience_helper')->isGroupAudienceField($field_definition)) {
      $field_name = $field_definition->getName();
      throw new \Exception("The field $field_name is not an audience field.");
    }

    $default_options = [
      'target_type' => $field_definition->getFieldStorageDefinition()->getSetting('target_type'),
      'handler' => $field_definition->getSetting('handler'),
      'field_mode' => 'default',
    ] + $field_definition->getSetting('handler_settings');

    // Override with passed $options.
    $options = NestedArray::mergeDeep($default_options, $options);

    return \Drupal::service('plugin.manager.entity_reference_selection')->createInstance('og:default', $options);
  }

  /**
   * Resets the static cache.
   */
  public static function reset() {
    static::$cache = [];
  }

}
