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
   */
  public function getEntityOperationPermissions(array $group_content_bundle_ids) {
    $permissions = [];

    foreach ($group_content_bundle_ids as $group_content_entity_type_id => $bundle_ids) {
      foreach ($bundle_ids as $bundle_id) {
        $permissions += $this->generateEntityOperationPermissionList($group_content_entity_type_id, $bundle_id);
      }
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function generateEntityOperationPermissionList($group_content_entity_type_id, $group_content_bundle_id) {
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
    $operations = [
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
    foreach ($operations as $name => $title) {
      $permission = new GroupContentOperationPermission();
      $permission
        ->setName($name)
        ->setTitle($title)
        ->setEntityType($group_content_entity_type_id)
        ->setBundle($group_content_bundle_id);
      $permissions[] = $permission;
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
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
