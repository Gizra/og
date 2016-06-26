<?php

namespace Drupal\og\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\og\Event\DefaultRoleEventInterface;
use Drupal\og\Event\GroupCreationEventInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
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
    ] + $this->permissionManager->getPermissionList($event->getEntityTypeId(), $event->getBundleId()));
  }

  /**
   * Provides a default role for the group administrator.
   *
   * @param \Drupal\og\Event\DefaultRoleEventInterface $event
   *   The default role event.
   */
  public function provideDefaultRoles(DefaultRoleEventInterface $event) {
    $role = $this->ogRoleStorage->create([
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
  public function createUserGroupAudienceField(GroupCreationEventInterface $event) {
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

}
