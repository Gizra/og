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
  public function getDefaultPermissions($group_entity_type_id, $group_bundle_id, $role_name = NULL) {
    // Populate the default permissions.
    $event = new PermissionEvent($group_entity_type_id, $group_bundle_id, []);
    $this->eventDispatcher->dispatch(PermissionEventInterface::EVENT_NAME, $event);

    $permissions = $event->getPermissions();
    if (!empty($role_name)) {
      $permissions = array_filter($permissions, function (PermissionInterface $permission) use ($role_name) {
        return !empty($permission->getDefaultRoles()) && in_array($role_name, $permission->getDefaultRoles());
      });
    }

    return $permissions;
  }

}
