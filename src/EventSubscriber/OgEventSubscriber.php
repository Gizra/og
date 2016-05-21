<?php

namespace Drupal\og\EventSubscriber;

use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\OgRoleInterface;
use Drupal\og\PermissionManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Organic Groups.
 */
class OgEventSubscriber implements EventSubscriberInterface {

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManager
   */
  protected $permissionManager;

  /**
   * Constructs an OgEventSubscriber object.
   *
   * @param \Drupal\og\PermissionManager $permission_manager
   *   The OG permission manager.
   */
  public function __construct(PermissionManager $permission_manager) {
    $this->permissionManager = $permission_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideDefaultOgPermissions']],
    ];
  }

  /**
   * Provides default OG permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideDefaultOgPermissions(PermissionEventInterface $event) {
    $event->setPermissions([
      'update group' => [
        'title' => t('Edit group'),
        'description' => t('Edit the group. Note: This permission controls only node entity type groups.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ],
      'administer group' => [
        'title' => t('Administer group'),
        'description' => t('Manage group members and content in the group.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ],
    ] + $this->permissionManager->generatePermissionList($event->getEntityTypeId(), $event->getBundleId()));
  }

}
