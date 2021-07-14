<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\Event\GroupContentEntityOperationAccessEvent;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The service that determines if users have access to groups and group content.
 */
class OgAccess implements OgAccessInterface {

  /**
   * Group level permission that grants full access to the group.
   *
   * Not to be confused with the 'administer organic groups' global permission
   * which is intended for site builders and gives full access to _all_ groups.
   */
  const ADMINISTER_GROUP_PERMISSION = 'administer group';

  /**
   * Group level permission that allows the user to delete the group entity.
   */
  const DELETE_GROUP_PERMISSION = 'delete group';

  /**
   * Group level permission that allows the user to update the group entity.
   */
  const UPDATE_GROUP_PERMISSION = 'update group';

  /**
   * Maps entity operations performed on groups to group level permissions.
   */
  const OPERATION_GROUP_PERMISSION_MAPPING = [
    'delete' => self::DELETE_GROUP_PERMISSION,
    'update' => self::UPDATE_GROUP_PERMISSION,
  ];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The service that contains the current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The group manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * The group membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Constructs the OgAccess service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The service that contains the current active user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\og\GroupTypeManagerInterface $group_manager
   *   The group manager.
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The permission manager.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The group membership manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $account_proxy, ModuleHandlerInterface $module_handler, GroupTypeManagerInterface $group_manager, PermissionManagerInterface $permission_manager, MembershipManagerInterface $membership_manager, EventDispatcherInterface $dispatcher) {
    $this->configFactory = $config_factory;
    $this->accountProxy = $account_proxy;
    $this->moduleHandler = $module_handler;
    $this->groupTypeManager = $group_manager;
    $this->permissionManager = $permission_manager;
    $this->membershipManager = $membership_manager;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function userAccess(EntityInterface $group, string $permission, ?AccountInterface $user = NULL, bool $skip_alter = FALSE): AccessResultInterface {
    $group_type_id = $group->getEntityTypeId();
    $bundle = $group->bundle();
    // As Og::isGroup depends on this config, we retrieve it here and set it as
    // the minimal caching data.
    $config = $this->configFactory->get('og.settings');
    $cacheable_metadata = (new CacheableMetadata())
      ->addCacheableDependency($config);

    if (!$this->groupTypeManager->isGroup($group_type_id, $bundle)) {
      // Not a group.
      return AccessResult::neutral()->addCacheableDependency($cacheable_metadata);
    }

    if (!isset($user)) {
      $user = $this->accountProxy->getAccount();
    }

    // From this point on, every result also depends on the user so check
    // whether it is the current. See https://www.drupal.org/node/2628870
    // @todo This doesn't really vary by user but by the user's roles inside of
    //   the group. We should create a cache context for OgRole entities.
    // @see https://github.com/amitaibu/og/issues/219
    if ($user->id() == $this->accountProxy->id()) {
      $cacheable_metadata->addCacheContexts(['user']);
    }

    // User ID 1 has all privileges.
    if ($user->id() == 1) {
      return AccessResult::allowed()->addCacheableDependency($cacheable_metadata);
    }

    // Check if the user has a global permission to administer all groups. This
    // gives full access.
    $user_access = AccessResult::allowedIfHasPermission($user, 'administer organic groups');
    if ($user_access->isAllowed()) {
      return $user_access->addCacheableDependency($cacheable_metadata);
    }

    if ($config->get('group_manager_full_access') && $user->isAuthenticated() && $group instanceof EntityOwnerInterface) {
      $cacheable_metadata->addCacheableDependency($group);
      if ($group->getOwnerId() == $user->id()) {
        return AccessResult::allowed()->addCacheableDependency($cacheable_metadata);
      }
    }

    $permissions = [];
    $user_is_group_admin = FALSE;
    if ($membership = $this->membershipManager->getMembership($group, $user->id())) {
      foreach ($membership->getRoles() as $role) {
        // Check for the is_admin flag.
        if ($role->isAdmin()) {
          $user_is_group_admin = TRUE;
          break;
        }

        $permissions = array_merge($permissions, $role->getPermissions());
      }
    }
    elseif (!$this->membershipManager->isMember($group, $user->id(), [OgMembershipInterface::STATE_BLOCKED])) {
      // User is a non-member or has a pending membership.
      /** @var \Drupal\og\Entity\OgRole $role */
      $role = OgRole::loadByGroupAndName($group, OgRoleInterface::ANONYMOUS);
      if ($role) {
        $permissions = $role->getPermissions();
      }
    }

    $permissions = array_unique($permissions);

    if (!$skip_alter && !in_array($permission, $permissions)) {
      // Let modules alter the permissions.
      $context = [
        'permission' => $permission,
        'group' => $group,
        'user' => $user,
      ];
      $this->moduleHandler->alter('og_user_access', $permissions, $cacheable_metadata, $context);
    }

    // Check if the user is a group admin and who has access to all the group
    // permissions.
    // @todo It should be possible for modules to alter the permissions even if
    //   the user is a group admin, UID 1 or has 'administer group' permission.
    if ($user_is_group_admin || in_array($permission, $permissions)) {
      // User is a group admin, and we do not ignore this special permission
      // that grants access to all the group permissions.
      return AccessResult::allowed()->addCacheableDependency($cacheable_metadata);
    }

    return AccessResult::neutral()->addCacheableDependency($cacheable_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function userAccessEntity(string $permission, EntityInterface $entity, ?AccountInterface $user = NULL): AccessResultInterface {
    $result = AccessResult::neutral();

    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $bundle = $entity->bundle();

    if ($this->groupTypeManager->isGroup($entity_type_id, $bundle)) {
      // An entity can be a group and group content in the same time. If the
      // group returns a neutral result the user still might have access to
      // the permission in group content context. So if we get a neutral result
      // we will continue with the group content access check below.
      $result = $this->userAccess($entity, $permission, $user);
      if (!$result->isNeutral()) {
        return $result;
      }
    }

    if ($this->groupTypeManager->isGroupContent($entity_type_id, $bundle)) {
      $result->addCacheTags($entity_type->getListCacheTags());

      // The entity might be a user or a non-user entity.
      $groups = $entity instanceof UserInterface ? $this->membershipManager->getUserGroups($entity->id()) : $this->membershipManager->getGroups($entity);

      if ($groups) {
        foreach ($groups as $entity_groups) {
          foreach ($entity_groups as $group) {
            $result = $result->orIf($this->userAccess($group, $permission, $user));
          }
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function userAccessEntityOperation(string $operation, EntityInterface $entity, ?AccountInterface $user = NULL): AccessResultInterface {
    $result = AccessResult::neutral();

    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $bundle = $entity->bundle();

    if ($this->groupTypeManager->isGroup($entity_type_id, $bundle)) {
      // We are performing an entity operation on a group entity. Map the
      // operation to the corresponding group level permission.
      if (array_key_exists($operation, self::OPERATION_GROUP_PERMISSION_MAPPING)) {
        $permission = self::OPERATION_GROUP_PERMISSION_MAPPING[$operation];

        // An entity can be a group and group content in the same time. If the
        // group returns a neutral result the user still might have access to
        // the permission in group content context. So if we get a neutral
        // result we will continue with the group content access check below.
        $result = $this->userAccess($entity, $permission, $user);
        if (!$result->isNeutral()) {
          return $result;
        }
      }
    }

    if ($this->groupTypeManager->isGroupContent($entity_type_id, $bundle)) {
      $result->addCacheTags($entity_type->getListCacheTags());

      // The entity might be a user or a non-user entity.
      $groups = $entity instanceof UserInterface ? $this->membershipManager->getUserGroups($entity->id()) : $this->membershipManager->getGroups($entity);

      if ($groups) {
        foreach ($groups as $entity_groups) {
          foreach ($entity_groups as $group) {
            $result = $result->orIf($this->userAccessGroupContentEntityOperation($operation, $group, $entity, $user));
          }
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function userAccessGroupContentEntityOperation(string $operation, EntityInterface $group_entity, EntityInterface $group_content_entity, ?AccountInterface $user = NULL): AccessResultInterface {
    // Default to the current user.
    $user = $user ?: $this->accountProxy->getAccount();

    $event = new GroupContentEntityOperationAccessEvent($operation, $group_entity, $group_content_entity, $user);

    // @todo This doesn't really vary by user but by the user's roles inside of
    //   the group. We should create a cache context for OgRole entities.
    // @see https://github.com/amitaibu/og/issues/219
    $event->addCacheableDependency($group_content_entity);
    if ($user->id() == $this->accountProxy->id()) {
      $event->addCacheContexts(['user']);
    }

    $this->dispatcher->dispatch(GroupContentEntityOperationAccessEvent::EVENT_NAME, $event);

    return $event->getAccessResult();
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void {
    trigger_error('OgAccessInterface::reset() is deprecated in og:8.1.0-alpha6 and is removed from og:8.1.0-beta1. The static cache has been removed and this method no longer serves any purpose. Any calls to this method can safely be removed. See https://github.com/Gizra/og/issues/654', E_USER_DEPRECATED);
  }

}
