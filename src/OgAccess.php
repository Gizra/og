<?php

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
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

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
   * Group level permission that allows the user to update the group entity.
   */
  const UPDATE_GROUP_PERMISSION = 'update group';

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
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * Constructs an OgManager service.
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
   * @param \Drupal\og\OgGroupAudienceHelperInterface $group_audience_helper
   *   The OG group audience helper.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $account_proxy, ModuleHandlerInterface $module_handler, GroupTypeManagerInterface $group_manager, PermissionManagerInterface $permission_manager, MembershipManagerInterface $membership_manager, OgGroupAudienceHelperInterface $group_audience_helper) {
    $this->configFactory = $config_factory;
    $this->accountProxy = $account_proxy;
    $this->moduleHandler = $module_handler;
    $this->groupTypeManager = $group_manager;
    $this->permissionManager = $permission_manager;
    $this->membershipManager = $membership_manager;
    $this->groupAudienceHelper = $group_audience_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function userAccess(EntityInterface $group, $operation, AccountInterface $user = NULL, $skip_alter = FALSE): AccessResultInterface {
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

    // Update group special permission. At this point, the operation should have
    // already been handled by Og. If the operation is simply 'edit'
    // (or 'update' for content entities), it is referring to the current group,
    // so we have to map it to the special permission.
    if (in_array($operation, ['update', 'edit'])) {
      $operation = OgAccess::UPDATE_GROUP_PERMISSION;
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
      $permissions = $role->getPermissions();
    }

    $permissions = array_unique($permissions);

    if (!$skip_alter && !in_array($operation, $permissions)) {
      // Let modules alter the permissions.
      $context = [
        'operation' => $operation,
        'group' => $group,
        'user' => $user,
      ];
      $this->moduleHandler->alter('og_user_access', $permissions, $cacheable_metadata, $context);
    }

    // Check if the user is a group admin and who has access to all the group
    // permissions.
    // @todo It should be possible for modules to alter the permissions even if
    //   the user is a group admin, UID 1 or has 'administer group' permission.
    if ($user_is_group_admin || in_array($operation, $permissions)) {
      // User is a group admin, and we do not ignore this special permission
      // that grants access to all the group permissions.
      return AccessResult::allowed()->addCacheableDependency($cacheable_metadata);
    }

    return AccessResult::forbidden()->addCacheableDependency($cacheable_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function userAccessEntity($operation, EntityInterface $entity, AccountInterface $user = NULL): AccessResultInterface {
    $result = AccessResult::neutral();

    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $bundle = $entity->bundle();

    if ($this->groupTypeManager->isGroup($entity_type_id, $bundle)) {
      $user_access = $this->userAccess($entity, $operation, $user);
      if ($user_access->isAllowed()) {
        return $user_access;
      }
      else {
        // An entity can be a group and group content in the same time. The
        // group didn't allow access, but the user still might have access to
        // the permission in group content context. So instead of retuning a
        // deny here, we set the result, that might change if an access is
        // found.
        $result = AccessResult::forbidden()->inheritCacheability($user_access);
      }
    }

    $is_group_content = $this->groupAudienceHelper->hasGroupAudienceField($entity_type_id, $bundle);
    if ($is_group_content) {
      $cache_tags = $entity_type->getListCacheTags();

      // The entity might be a user or a non-user entity.
      $groups = $entity instanceof UserInterface ? $this->membershipManager->getUserGroups($entity->id()) : $this->membershipManager->getGroups($entity);

      if ($groups) {
        $forbidden = AccessResult::forbidden()->addCacheTags($cache_tags);
        foreach ($groups as $entity_groups) {
          foreach ($entity_groups as $group) {
            // Check if the operation matches a group content entity operation
            // such as 'create article content'.
            $operation_access = $this->userAccessGroupContentEntityOperation($operation, $group, $entity, $user);

            if ($operation_access->isAllowed()) {
              return $operation_access->addCacheTags($cache_tags);
            }

            // Check if the operation matches a group level operation such as
            // 'subscribe without approval'.
            $user_access = $this->userAccess($group, $operation, $user);
            if ($user_access->isAllowed()) {
              return $user_access->addCacheTags($cache_tags);
            }

            $forbidden->inheritCacheability($user_access);
          }
        }
        return $forbidden;
      }

      $result->addCacheTags($cache_tags);
    }

    // Either the user didn't have permission, or the entity might be an
    // orphaned group content.
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function userAccessGroupContentEntityOperation($operation, EntityInterface $group_entity, EntityInterface $group_content_entity, AccountInterface $user = NULL): AccessResultInterface {
    // Default to the current user.
    $user = $user ?: $this->accountProxy->getAccount();

    // Check if the user owns the entity which is being operated on.
    $is_owner = $group_content_entity instanceof EntityOwnerInterface && $group_content_entity->getOwnerId() == $user->id();

    // Retrieve the group content entity operation permissions.
    $group_entity_type_id = $group_entity->getEntityTypeId();
    $group_bundle_id = $group_entity->bundle();
    $group_content_bundle_ids = [$group_content_entity->getEntityTypeId() => [$group_content_entity->bundle()]];

    $permissions = $this->permissionManager->getDefaultEntityOperationPermissions($group_entity_type_id, $group_bundle_id, $group_content_bundle_ids);

    // Filter the permissions by operation and ownership.
    // If the user does not own the group content, only the non-owner permission
    // is relevant (for example 'edit any article node'). However when the user
    // _is_ the owner, then both permissions are relevant: an owner will have
    // access if they either have the 'edit any article node' or the 'edit own
    // article node' permission.
    $ownerships = $is_owner ? [FALSE, TRUE] : [FALSE];
    $permissions = array_filter($permissions, function (GroupContentOperationPermission $permission) use ($operation, $ownerships) {
      return $permission->getOperation() === $operation && in_array($permission->getOwner(), $ownerships);
    });

    if ($permissions) {
      foreach ($permissions as $permission) {
        $user_access = $this->userAccess($group_entity, $permission->getName(), $user);
        if ($user_access->isAllowed()) {
          return $user_access;
        }
      }
    }

    // @todo This doesn't really vary by user but by the user's roles inside of
    //   the group. We should create a cache context for OgRole entities.
    // @see https://github.com/amitaibu/og/issues/219
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheableDependency($group_content_entity);
    if ($user->id() == $this->accountProxy->id()) {
      $cacheable_metadata->addCacheContexts(['user']);
    }

    return AccessResult::neutral()->addCacheableDependency($cacheable_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void {
    trigger_error('OgAccessInterface::reset() is deprecated in og:8.1.0-alpha6 and is removed from og:8.1.0-beta1. The static cache has been removed and this no longer server any purpose. See https://github.com/Gizra/og/issues/654', E_USER_DEPRECATED);
  }

}
