<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Event\PermissionEvent;
use Drupal\og\Event\PermissionEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manager for OG permissions.
 */
class PermissionManager implements PermissionManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * Constructs a PermissionManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Provide an alter hook.
   * @todo This only returns permissions related to entity operations on group
   *   content. Rename it accordingly and clarify in documentation.
   */
  public function getPermissionList(array $group_content_bundle_ids) {
    $permissions = [];

    foreach ($group_content_bundle_ids as $group_content_entity_type_id => $bundle_ids) {
      foreach ($bundle_ids as $bundle_id) {
        $permissions += $this->generateCrudPermissionList($group_content_entity_type_id, $bundle_id);
      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function generateCrudPermissionList($group_content_entity_type_id, $group_content_bundle_id) {
    $permissions = [];

    $entity_info = $this->entityTypeManager->getDefinition($group_content_entity_type_id);
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($group_content_entity_type_id)[$group_content_bundle_id];

    // Build standard list of permissions for this bundle.
    $args = [
      '%bundle' => $bundle_info['label'],
      '@entity' => $entity_info->getPluralLabel(),
    ];
    // @todo This needs to support all entity operations for the given entity
    //    type, not just the standard CRUD operations.
    // @see https://github.com/amitaibu/og/issues/222
    $permissions += [
      "create $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Create %bundle @entity', $args),
      ],
      "update own $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Edit own %bundle @entity', $args),
      ],
      "update any $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Edit any %bundle @entity', $args),
      ],
      "delete own $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Delete own %bundle @entity', $args),
      ],
      "delete any $group_content_bundle_id $group_content_entity_type_id" => [
        'title' => t('Delete any %bundle @entity', $args),
      ],
    ];

    // Add default permissions.
    foreach ($permissions as $key => $value) {
      $permissions[$key]['default role'] = [OgRoleInterface::ADMINISTRATOR];
    }

    return $permissions;
  }

  /**
   * Returns permissions that are enabled by default for the given role.
   *
   * @param string $group_entity_type_id
   *   The entity type ID of the group for which to return permissions.
   * @param string $group_bundle_id
   *   The bundle ID of the group for which to return permissions.
   * @param array $group_content_bundle_ids
   *   The bundle IDs of the group content associated with the group for which
   *   to return permissions, keyed by group content entity type ID.
   * @param string $role_name
   *   Optional default role name to filter the permissions on. If omitted, all
   *   permissions will be returned.
   *
   * @return array
   *   An array of permissions that are enabled by default for the given role.
   */
  public function getDefaultPermissions($group_entity_type_id, $group_bundle_id, array $group_content_bundle_ids, $role_name = NULL) {
    // Populate the default permissions.
    $event = new PermissionEvent($group_entity_type_id, $group_bundle_id, $group_content_bundle_ids);
    $this->eventDispatcher->dispatch(PermissionEventInterface::EVENT_NAME, $event);

    $permissions = $event->getPermissions();
    if (!empty($role_name)) {
      $permissions = array_filter($permissions, function ($permission) use ($role_name) {
        return !empty($permission['default roles']) && in_array($role_name, $permission['default roles']);
      });
    }

    return $permissions;
  }

}
