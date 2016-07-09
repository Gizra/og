<?php

namespace Drupal\og\EventSubscriber;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\DefaultRoleEventInterface;
use Drupal\og\Event\GroupCreationEventInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
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
        ['provideDefaultNodePermissions']
      ],
      DefaultRoleEventInterface::EVENT_NAME => [['provideDefaultRoles']],
      GroupCreationEventInterface::EVENT_NAME => [['createUserGroupAudienceField']]
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
   * Upon group creation, add an OG audience field to the user if it doesn't
   * exist.
   *
   * @param GroupCreationEventInterface $event
   *   The created group.
   */
  public function createUserGroupAudienceField(GroupCreationEventInterface $event)
  {
    $entity_type_id = $event->getEntityTypeId();
    $bundle_id = $event->getBundleId();

    // create a group audience field which will reference to groups from the
    // given entity type ID and attach it to the user.
    $fields = OgGroupAudienceHelper::getAllGroupAudienceFields('user', 'user');

    foreach ($fields as $field) {

      if ($field->getFieldStorageDefinition()->getSetting('target_type') == $entity_type_id) {

        if (!$field->getSetting('handler_settings')['target_bundles']) {
          // The field does not reference to any group bundle.
          return;
        }

        if (in_array($bundle_id, $field->getSetting('handler_settings')['target_bundles'])) {
          // The field doe not handle the current bundle.
          return;
        }
      }
    }

    // If we reached here, it means we need to create a field. Pick an unused
    // name but don't exceed the maximum characters to a field name.
    $field_name = substr("og_user_$entity_type_id", 0, 32);
    $i = 1;
    while (FieldConfig::loadByName($entity_type_id, $bundle_id, $field_name)) {
      $field_name = substr("og_user_$entity_type_id", 0, 32 - strlen($i)) . $i;
      ++$i;
    }

    $user_bundles = \Drupal::entityTypeManager()->getDefinition('user')->getKey('bundle') ?: ['user'];

    $settings = [
      'field_name' => $field_name,
      'field_storage_config' => [
        'settings' => [
          'target_type' => $entity_type_id,
        ],
      ],
      'field_config' => [
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [$bundle_id => $bundle_id],
          ],
        ],
      ],
    ];

    foreach ($user_bundles as $user_bundle) {
      Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', $user_bundle, $settings);
    }
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
   * @param $group_content_entity_type_id
   *   The entity type ID for which to generate the permission list.
   * @param $group_content_bundle_id
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
    //    type, not just the standard CRUD operations.
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

}
