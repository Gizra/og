<?php

namespace Drupal\og;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
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
   * Administer permission string.
   *
   * @var string
   */
  const ADMINISTER_GROUP_PERMISSION = 'administer group';

  /**
   * Update group permission string.
   *
   * @var string
   */
  const UPDATE_GROUP_PERMISSION = 'update group';

  /**
   * Static cache that contains cache permissions.
   *
   * @var array
   *   Array keyed by the following keys:
   *   - alter: The permissions after altered by implementing modules.
   *   - pre_alter: The pre-altered permissions, as read from the config.
   */
  protected $permissionsCache = [];

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
  public function userAccess(EntityInterface $group, $operation, AccountInterface $user = NULL, $skip_alter = FALSE, $ignore_admin = FALSE) {
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

    // Administer group permission.
    if (!$ignore_admin) {
      $user_access = AccessResult::allowedIfHasPermission($user, self::ADMINISTER_GROUP_PERMISSION);
      if ($user_access->isAllowed()) {
        return $user_access->addCacheableDependency($cacheable_metadata);
      }
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

    $pre_alter_cache = $this->getPermissionsCache($group, $user, TRUE);
    $post_alter_cache = $this->getPermissionsCache($group, $user, FALSE);

    // To reduce the number of SQL queries, we cache the user's permissions.
    if (!$pre_alter_cache) {
      $permissions = [];
      $user_is_group_admin = FALSE;
      if ($membership = $this->membershipManager->getMembership($group, $user->id())) {
        foreach ($membership->getRoles() as $role) {
          // Check for the is_admin flag.
          /** @var \Drupal\og\Entity\OgRole $role */
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

      $this->setPermissionCache($group, $user, TRUE, $permissions, $user_is_group_admin, $cacheable_metadata);
    }

    if (!$skip_alter && !in_array($operation, $post_alter_cache)) {
      // Let modules alter the permissions. So we get the original ones, and
      // pass them along to the implementing modules.
      $alterable_permissions = $this->getPermissionsCache($group, $user, TRUE);

      $context = [
        'operation' => $operation,
        'group' => $group,
        'user' => $user,
      ];
      $this->moduleHandler->alter('og_user_access', $alterable_permissions['permissions'], $cacheable_metadata, $context);

      $this->setPermissionCache($group, $user, FALSE, $alterable_permissions['permissions'], $alterable_permissions['is_admin'], $cacheable_metadata);
    }

    $altered_permissions = $this->getPermissionsCache($group, $user, FALSE);

    $user_is_group_admin = !empty($altered_permissions['is_admin']);

    if (($user_is_group_admin && !$ignore_admin) || in_array($operation, $altered_permissions['permissions'])) {
      // User is a group admin, and we do not ignore this special permission
      // that grants access to all the group permissions.
      return AccessResult::allowed()->addCacheableDependency($altered_permissions['cacheable_metadata']);
    }

    return AccessResult::forbidden()->addCacheableDependency($cacheable_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function userAccessEntity($operation, EntityInterface $entity, AccountInterface $user = NULL) {
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
  public function userAccessGroupContentEntityOperation($operation, EntityInterface $group_entity, EntityInterface $group_content_entity, AccountInterface $user = NULL) {
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
   * Set the permissions in the static cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param bool $pre_alter
   *   Determines if the type of permissions is pre-alter or post-alter.
   * @param array $permissions
   *   Array of permissions to set.
   * @param bool $is_admin
   *   Whether or not the user is a group administrator.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheable_metadata
   *   A cacheable metadata object.
   */
  protected function setPermissionCache(EntityInterface $group, AccountInterface $user, $pre_alter, array $permissions, $is_admin, RefinableCacheableDependencyInterface $cacheable_metadata) {
    $entity_type_id = $group->getEntityTypeId();
    $group_id = $group->id();
    $user_id = $user->id();
    $type = $pre_alter ? 'pre_alter' : 'post_alter';

    $this->permissionsCache[$entity_type_id][$group_id][$user_id][$type] = [
      'is_admin' => $is_admin,
      'permissions' => $permissions,
      'cacheable_metadata' => $cacheable_metadata,
    ];
  }

  /**
   * Get the permissions from the static cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param bool $pre_alter
   *   Determines if the type of permissions is pre-alter or post-alter.
   *
   * @return array
   *   Array of permissions if cached, or an empty array.
   */
  protected function getPermissionsCache(EntityInterface $group, AccountInterface $user, $pre_alter) {
    $entity_type_id = $group->getEntityTypeId();
    $group_id = $group->id();
    $user_id = $user->id();
    $type = $pre_alter ? 'pre_alter' : 'post_alter';

    return isset($this->permissionsCache[$entity_type_id][$group_id][$user_id][$type]) ? $this->permissionsCache[$entity_type_id][$group_id][$user_id][$type] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->permissionsCache = [];
  }

}
