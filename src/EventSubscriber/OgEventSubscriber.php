<?php

namespace Drupal\og\EventSubscriber;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\DefaultRoleEventInterface;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
use Drupal\og\OgAccess;
use Drupal\og\OgRoleInterface;
use Drupal\og\PermissionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Organic Groups.
 */
class OgEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

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
   * Constructs an OgEventSubscriber object.
   *
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   */
  public function __construct(PermissionManagerInterface $permission_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->permissionManager = $permission_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [
        // Provide a higher priority for the generic event subscriber so that it
        // can run first and set default values for all supported entity types,
        // which can then be overridden by other subscribers that set module
        // specific permissions.
        ['provideDefaultOgPermissions', 10],
        ['provideDefaultNodePermissions'],
      ],
      DefaultRoleEventInterface::EVENT_NAME => [['provideDefaultRoles']],
      OgAdminRoutesEventInterface::EVENT_NAME => [['provideOgAdminRoutes']],
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
        'name' => OgAccess::UPDATE_GROUP_PERMISSION,
        'title' => t('Edit group'),
        'description' => t('Edit the group. Note: This permission controls only node entity type groups.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
      new GroupPermission([
        'name' => OgAccess::ADMINISTER_GROUP_PERMISSION,
        'title' => t('Administer group'),
        'description' => t('Manage group members and content in the group.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ]),
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
        'name' => 'approve and deny subscription',
        'title' => t('Approve and deny subscription'),
        'description' => t("Users may allow or deny another user's subscription request."),
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
        'name' => 'administer permissions',
        'title' => t('Administer permissions'),
        'description' => t('Users may view, create, edit and delete permissions and roles within the group.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ]),
    ]);

    // Add a list of generic CRUD permissions for all group content.
    $group_content_permissions = $this->getDefaultEntityOperationPermissions($event->getGroupContentBundleIds());
    $event->setPermissions($group_content_permissions);
  }

  /**
   * Provides default permissions for the Node entity.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideDefaultNodePermissions(PermissionEventInterface $event) {
    $bundle_ids = $event->getGroupContentBundleIds();

    if (!array_key_exists('node', $bundle_ids)) {
      return;
    }

    $permissions = [];
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('node');

    foreach ($bundle_ids['node'] as $bundle_id) {
      $args = ['%type_name' => $bundle_info[$bundle_id]['label']];
      $permission_values = [
        [
          'name' => "create $bundle_id content",
          'title' => $this->t('%type_name: Create new content', $args),
          'operation' => 'create',
        ],
        [
          'name' => "edit own $bundle_id content",
          'title' => $this->t('%type_name: Edit own content', $args),
          'operation' => 'update',
          'owner' => TRUE,
        ],
        [
          'name' => "edit any $bundle_id content",
          'title' => $this->t('%type_name: Edit any content', $args),
          'operation' => 'update',
          'owner' => FALSE,
        ],
        [
          'name' => "delete own $bundle_id content",
          'title' => $this->t('%type_name: Delete own content', $args),
          'operation' => 'delete',
          'owner' => TRUE,
        ],
        [
          'name' => "delete any $bundle_id content",
          'title' => $this->t('%type_name: Delete any content', $args),
          'operation' => 'delete',
          'owner' => FALSE,
        ],
      ];
      foreach ($permission_values as $values) {
        $values += [
          'entity type' => 'node',
          'bundle' => $bundle_id,
        ];
        $permissions[] = new GroupContentOperationPermission($values);
      }
    }

    $event->setPermissions($permissions);
  }

  /**
   * Provides a default role for the group administrator.
   *
   * @param \Drupal\og\Event\DefaultRoleEventInterface $event
   *   The default role event.
   */
  public function provideDefaultRoles(DefaultRoleEventInterface $event) {
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = $this->entityTypeManager->getStorage('og_role')->create([
      'name' => OgRoleInterface::ADMINISTRATOR,
      'label' => 'Administrator',
      'is_admin' => TRUE,
    ]);
    $event->addRole($role);
  }

  /**
   * Returns a list of generic entity operation permissions for group content.
   *
   * This returns generic group content entity operation permissions for the
   * operations 'create', 'update' and 'delete'.
   *
   * In Drupal the entity operation permissions are not following a machine
   * writable naming scheme, but instead they use an arbitrary human readable
   * format. For example the permission to update nodes of type article is 'edit
   * own article content'. This does not even contain the operation 'update' or
   * the entity type 'node'.
   *
   * OG needs to be able to provide basic CRUD permissions for its group content
   * even if it cannot generate the proper human readable versions. This method
   * settles for a generic permission format '{operation} {ownership} {bundle}
   * {entity type}'. For example for editing articles this would become 'update
   * own article node'.
   *
   * Modules can implement their own PermissionEvent to declare their proper
   * permissions to use instead of the generic ones. For an example
   * implementation, see `provideDefaultNodePermissions()`.
   *
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs, keyed by group content entity type
   *   ID.
   *
   * @return \Drupal\og\GroupContentOperationPermission[]
   *   The array of permissions.
   *
   * @see \Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultNodePermissions()
   */
  protected function getDefaultEntityOperationPermissions(array $group_content_bundle_ids) {
    $permissions = [];

    foreach ($group_content_bundle_ids as $group_content_entity_type_id => $bundle_ids) {
      foreach ($bundle_ids as $bundle_id) {
        $permissions += $this->generateEntityOperationPermissionList($group_content_entity_type_id, $bundle_id);
      }
    }

    return $permissions;
  }

  /**
   * Helper method to generate entity operation permissions for a given bundle.
   *
   * @param string $group_content_entity_type_id
   *   The entity type ID for which to generate the permission list.
   * @param string $group_content_bundle_id
   *   The bundle ID for which to generate the permission list.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function generateEntityOperationPermissionList($group_content_entity_type_id, $group_content_bundle_id) {
    $permissions = [];

    $entity_info = $this->entityTypeManager->getDefinition($group_content_entity_type_id);
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($group_content_entity_type_id)[$group_content_bundle_id];

    // Build standard list of permissions for this bundle.
    $args = [
      '%bundle' => $bundle_info['label'],
      '@entity' => $entity_info->getPluralLabel(),
    ];
    // @todo This needs to support all entity operations for the given entity
    //   type, not just the standard CRUD operations.
    // @see https://github.com/amitaibu/og/issues/222
    $operations = [
      [
        'name' => "create $group_content_bundle_id $group_content_entity_type_id",
        'title' => $this->t('Create %bundle @entity', $args),
        'operation' => 'create',
      ],
      [
        'name' => "update own $group_content_bundle_id $group_content_entity_type_id",
        'title' => $this->t('Edit own %bundle @entity', $args),
        'operation' => 'update',
        'owner' => TRUE,
      ],
      [
        'name' => "update any $group_content_bundle_id $group_content_entity_type_id",
        'title' => $this->t('Edit any %bundle @entity', $args),
        'operation' => 'update',
        'owner' => FALSE,
      ],
      [
        'name' => "delete own $group_content_bundle_id $group_content_entity_type_id",
        'title' => $this->t('Delete own %bundle @entity', $args),
        'operation' => 'delete',
        'owner' => TRUE,
      ],
      [
        'name' => "delete any $group_content_bundle_id $group_content_entity_type_id",
        'title' => $this->t('Delete any %bundle @entity', $args),
        'operation' => 'delete',
        'owner' => FALSE,
      ],
    ];

    // Add default permissions.
    foreach ($operations as $values) {
      $permission = new GroupContentOperationPermission($values);
      $permission
        ->setEntityType($group_content_entity_type_id)
        ->setBundle($group_content_bundle_id)
        ->setDefaultRoles([OgRoleInterface::ADMINISTRATOR]);
      $permissions[] = $permission;
    }

    return $permissions;
  }

  /**
   * Provide OG admin routes.
   *
   * @param \Drupal\og\Event\OgAdminRoutesEventInterface $event
   *   The OG admin routes event object.
   */
  public function provideOgAdminRoutes(OgAdminRoutesEventInterface $event) {
    $routes_info = $event->getRoutesInfo();

    $routes_info['members'] = [
      'controller' => '\Drupal\og\Controller\OgAdminMembersController::membersList',
      'title' => 'Members',
      'description' => 'Manage members',
      'path' => 'members',
      'requirements' => [
        '_og_user_access_group' => 'administer group',
        // Views module must be enabled.
        '_module_dependencies' => 'views',
      ],
    ];

    $event->setRoutesInfo($routes_info);
  }

}
