<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Event\DefaultRoleEvent;
use Drupal\og\Event\DefaultRoleEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a manager of an OG role.
 */
class OgRoleManager implements OgRoleManagerInterface {

  /**
   * The entity storage for OgRole entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogRoleStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * Constructs an OgRoleManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, PermissionManagerInterface $permission_manager) {
    $this->ogRoleStorage = $entity_type_manager->getStorage('og_role');
    $this->eventDispatcher = $event_dispatcher;
    $this->permissionManager = $permission_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createPerBundleRoles($entity_type_id, $bundle_id) {
    $roles = [];
    foreach ($this->getDefaultRoles() as $role) {
      $role->setGroupType($entity_type_id);
      $role->setGroupBundle($bundle_id);

      // Populate the default roles with a set of default permissions.
      $permissions = $this->permissionManager->getDefaultGroupPermissions($entity_type_id, $bundle_id, $role->getName());
      foreach (array_keys($permissions) as $permission) {
        $role->grantPermission($permission);
      }

      $role->save();
      $roles[] = $role;
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultRoles() {
    // Provide the required default roles: 'member' and 'non-member'.
    $roles = $this->getRequiredDefaultRoles();

    $event = new DefaultRoleEvent();
    $this->eventDispatcher->dispatch(DefaultRoleEventInterface::EVENT_NAME, $event);

    // Use the array union operator '+=' to ensure the default roles cannot be
    // altered by event subscribers.
    $roles += $event->getRoles();

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredDefaultRoles() {
    $roles = [];

    $role_properties = [
        [
          'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          'label' => 'Non-member',
          'name' => OgRoleInterface::ANONYMOUS,
        ],
        [
          'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          'label' => 'Member',
          'name' => OgRoleInterface::AUTHENTICATED,
        ],
    ];

    foreach ($role_properties as $properties) {
      $roles[$properties['name']] = $this->ogRoleStorage->create($properties);
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesByBundle($entity_type_id, $bundle) {
    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle,
    ];
    return $this->ogRoleStorage->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function removeRoles($entity_type_id, $bundle_id) {
    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle_id,
    ];
    foreach ($this->ogRoleStorage->loadByProperties($properties) as $role) {
      $role->delete();
    }
  }

}
