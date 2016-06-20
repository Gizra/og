<?php

namespace Drupal\og\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Event\DefaultRoleEventInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Drupal\og\PermissionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Organic Groups.
 */
class OgEventSubscriber implements EventSubscriberInterface {

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * The storage handler for OgRole entities.
   *
   * @var \Drupal\core\Entity\EntityStorageInterface
   */
  protected $ogRoleStorage;

  /**
   * Constructs an OgEventSubscriber object.
   *
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission manager.
   * @param \Drupal\core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PermissionManagerInterface $permission_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->permissionManager = $permission_manager;
    $this->ogRoleStorage = $entity_type_manager->getStorage('og_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideDefaultOgPermissions']],
      DefaultRoleEventInterface::EVENT_NAME => [['provideDefaultRoles']],
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
        'name' => 'update group',
        'title' => t('Edit group'),
        'description' => t('Edit the group. Note: This permission controls only node entity type groups.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
      new GroupPermission([
        'name' => 'administer group',
        'title' => t('Administer group'),
        'description' => t('Manage group members and content in the group.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ]),
    ]);

    // Add a list of generic CRUD permissions for all group content.
    $group_content_permissions = $this->permissionManager->getEntityOperationPermissions($event->getGroupContentBundleIds());
    $event->setPermissions($group_content_permissions);
  }

  /**
   * Provides a default role for the group administrator.
   *
   * @param \Drupal\og\Event\DefaultRoleEventInterface $event
   *   The default role event.
   */
  public function provideDefaultRoles(DefaultRoleEventInterface $event) {
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = $this->ogRoleStorage->create([
      'name' => OgRoleInterface::ADMINISTRATOR,
      'label' => 'Administrator',
      'is_admin' => TRUE,
    ]);
    $event->addRole($role);
  }

}
