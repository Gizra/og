<?php

namespace Drupal\og_ui\EventSubscriber;

use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Organic Groups.
 */
class OgUiEventSubscriber implements EventSubscriberInterface {

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
      new GroupPermission([
        'name' => 'subscribe',
        'title' => t('Subscribe to group'),
        'description' => t('Allow non-members to request membership to a group (approval required).'),
        'roles' => [OgRoleInterface::ANONYMOUS],
        'default roles' => [OgRoleInterface::ANONYMOUS],
      ]),
      new GroupPermission([
        'name' => 'subscribe without approval',
        'title' => t('Subscribe to group (no approval required)'),
        'description' => t('Allow non-members to join a group without an approval from group administrators.'),
        'roles' => [OgRoleInterface::ANONYMOUS],
        'default roles' => [],
      ]),
      new GroupPermission([
        'name' => 'unsubscribe',
        'title' => t('Unsubscribe from group'),
        'description' => t('Allow members to unsubscribe themselves from a group, removing their membership.'),
        'roles' => [OgRoleInterface::AUTHENTICATED],
        'default roles' => [OgRoleInterface::AUTHENTICATED],
      ]),
      new GroupPermission([
        'name' => 'approve and deny subscription',
        'title' => t('Approve and deny subscription'),
        'description' => t('Users may allow or deny another user\'s subscription request.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
      new GroupPermission([
        'name' => 'add user',
        'title' => t('Add user'),
        'description' => t('Users may add other users to the group without approval.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
      new GroupPermission([
        'name' => 'manage members',
        'title' => t('Manage members'),
        'description' => t('Users may remove group members and alter member status and roles.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ]),
      new GroupPermission([
        'name' => 'manage roles',
        'title' => t('Add roles'),
        'description' => t('Users may view group roles and add new roles if group default roless are overridden.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ]),
      new GroupPermission([
        'name' => 'manage permissions',
        'title' => t('Manage permissions'),
        'description' => t('Users may view the group permissions page and change permissions if group default roless are overridden.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ]),
    ]);
  }

}
