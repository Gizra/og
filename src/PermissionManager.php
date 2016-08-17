<?php

namespace Drupal\og;

use Drupal\og\Event\PermissionEvent;
use Drupal\og\Event\PermissionEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manager for OG permissions.
 */
class PermissionManager implements PermissionManagerInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a PermissionManager object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultPermissions($group_entity_type_id, $group_bundle_id, array $group_content_bundle_ids, $role_name = NULL) {
    $event = new PermissionEvent($group_entity_type_id, $group_bundle_id, $group_content_bundle_ids);
    $this->eventDispatcher->dispatch(PermissionEventInterface::EVENT_NAME, $event);
    return $event->getPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultGroupPermissions($group_entity_type_id, $group_bundle_id, $role_name = NULL) {
    $permissions = $this->getDefaultPermissions($group_entity_type_id, $group_bundle_id, [], $role_name);

    $permissions = array_filter($permissions, function (PermissionInterface $permission) use ($role_name) {
      // Only keep group permissions.
      if (!$permission instanceof GroupPermission) {
        return FALSE;
      }

      // Optionally filter on role name.
      $default_roles = $permission->getDefaultRoles();
      return empty($role_name) || (!empty($default_roles) && in_array($role_name, $permission->getDefaultRoles()));
    });

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultEntityOperationPermissions($group_entity_type_id, $group_bundle_id, array $group_content_bundle_ids, $role_name = NULL) {
    $permissions = $this->getDefaultPermissions($group_entity_type_id, $group_bundle_id, $group_content_bundle_ids, $role_name);

    $permissions = array_filter($permissions, function (PermissionInterface $permission) use ($role_name) {
      // Only keep entity operation permissions.
      if (!$permission instanceof GroupContentOperationPermission) {
        return FALSE;
      }

      // Optionally filter on role name.
      $default_roles = $permission->getDefaultRoles();
      return empty($role_name) || (!empty($default_roles) && in_array($role_name, $permission->getDefaultRoles()));
    });

    return $permissions;
  }

}
