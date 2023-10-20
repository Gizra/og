<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Event\DefaultRoleEvent;
use Drupal\og\Event\DefaultRoleEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a manager of an OG role.
 */
class OgRoleManager implements OgRoleManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity storage for OgRole entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $ogRoleStorage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected PermissionManagerInterface $permissionManager;

  /**
   * Constructs an OgRoleManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, PermissionManagerInterface $permission_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->permissionManager = $permission_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createPerBundleRoles(string $entity_type_id, string $bundle_id): array {
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
  public function getDefaultRoles(): array {
    // Provide the required default roles: 'member' and 'non-member'.
    $roles = $this->getRequiredDefaultRoles();

    $event = new DefaultRoleEvent();
    $this->eventDispatcher->dispatch($event, DefaultRoleEventInterface::EVENT_NAME);

    // Use the array union operator '+=' to ensure the default roles cannot be
    // altered by event subscribers.
    $roles += $event->getRoles();

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredDefaultRoles(): array {
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
      $roles[$properties['name']] = $this->ogRoleStorage()->create($properties);
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesByBundle(string $entity_type_id, string $bundle): array {
    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle,
    ];
    return $this->ogRoleStorage()->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getRolesByPermissions(array $permissions, ?string $entity_type_id = NULL, ?string $bundle = NULL, bool $require_all = TRUE): array {
    $role_storage = $this->ogRoleStorage();
    $query = $role_storage->getQuery();
    if ($require_all) {
      // If all permissions are requested, we need to add an AND condition for
      // each permission because there is not an easy way to explicitly request
      // a subset of an array.
      foreach ($permissions as $permission) {
        $query->condition('permissions.*', $permission);
      }
    }
    else {
      $query->condition('permissions.*', $permissions, 'IN');
    }

    if (!empty($entity_type_id)) {
      $query->condition('group_type', $entity_type_id);
    }
    if (!empty($bundle)) {
      $query->condition('group_bundle', $bundle);
    }

    $role_ids = $query->execute();
    return $role_storage->loadMultiple($role_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function removeRoles(string $entity_type_id, string $bundle_id): void {
    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle_id,
    ];
    foreach ($this->ogRoleStorage()->loadByProperties($properties) as $role) {
      $role->delete();
    }
  }

  /**
   * Retrieves the OG Role storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The OG Role storage
   */
  protected function ogRoleStorage(): EntityStorageInterface {
    if (!isset($this->ogRoleStorage)) {
      $this->ogRoleStorage = $this->entityTypeManager->getStorage('og_role');
    }

    return $this->ogRoleStorage;
  }

}
