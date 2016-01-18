<?php

/**
 * @file
 * Contains \Drupal\og\OgAccess.
 */

namespace Drupal\og;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

class OgAccess {

  /**
   * Static cache that contains cache permissions.
   *
   * @var array
   *   Array keyed by the following keys:
   *   - alter: The permissions after altered by implementing modules.
   *   - pre_alter: The pre-altered permissions, as read from the config.
   */
  protected static $permissionsCache = ['pre_alter' => [], 'post_alter' => []];


  /**
   * Administer permission string.
   *
   * @var string
   */
  const ADMINISTER_GROUP_PERMISSION = 'administer group';

  /**
   * Determines whether a user has a given privilege.
   *
   * All permission checks in OG should go through this function. This
   * way, we guarantee consistent behavior, and ensure that the superuser
   * and group administrators can perform all actions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param string $operation
   *   The entity operation being checked for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   (optional) The user to check. Defaults to the current user.
   * @param $skip_alter
   *   (optional) If TRUE then user access will not be sent to other modules
   *   using drupal_alter(). This can be used by modules implementing
   *   hook_og_user_access_alter() that still want to use og_user_access(), but
   *   without causing a recursion. Defaults to FALSE.
   * @param $ignore_admin
   *   (optional) When TRUE the specific permission is checked, ignoring the
   *   "administer group" permission if the user has it. When FALSE, a user
   *   with "administer group" will be granted all permissions.
   *   Defaults to FALSE.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object.
   */
  public static function userAccess(EntityInterface $group, $operation, AccountInterface $user = NULL, $skip_alter = FALSE, $ignore_admin = FALSE) {
    $group_type_id = $group->getEntityTypeId();
    $bundle = $group->bundle();
    // As Og::isGroup depends on this config, we retrieve it here and set it as
    // the minimal caching data.
    $config = \Drupal::config('og.settings');
    $cacheable_metadata = (new CacheableMetadata)
        ->addCacheableDependency($config);
    if (!Og::isGroup($group_type_id, $bundle)) {
      // Not a group.
      return AccessResult::neutral()->addCacheableDependency($cacheable_metadata);
    }

    if (!isset($user)) {
      $user = \Drupal::currentUser()->getAccount();
    }

    // From this point on, every result also depends on the user so check
    // whether it is the current. See https://www.drupal.org/node/2628870
    if ($user->id() == \Drupal::currentUser()->id()) {
      $cacheable_metadata->addCacheContexts(['user']);
    }

    // User ID 1 has all privileges.
    if ($user->id() == 1) {
      return AccessResult::allowed()->addCacheableDependency($cacheable_metadata);
    }

    // Administer group permission.
    if (!$ignore_admin) {
      $user_access = AccessResult::allowedIfHasPermission($user, static::ADMINISTER_GROUP_PERMISSION);
      if ($user_access->isAllowed()) {
        return $user_access->addCacheableDependency($cacheable_metadata);
      }
    }

    // Group manager has all privileges (if variable is TRUE) and they are
    if ($config->get('group_manager_full_access') && $user->isAuthenticated() && $group instanceof EntityOwnerInterface) {
      $cacheable_metadata->addCacheableDependency($group);
      if ($group->getOwnerId() == $user->id()) {
        return AccessResult::allowed()->addCacheableDependency($cacheable_metadata);
      }
    }

    $pre_alter_cache = static::getPermissionsCache($group, $user, TRUE);
    $post_alter_cache = static::getPermissionsCache($group, $user, FALSE);

    // To reduce the number of SQL queries, we cache the user's permissions
    // in a static variable.
    if (!$pre_alter_cache) {
      $permissions = array();

      // @todo: Getting permissions from OG Roles will be added here.

      static::setPermissionCache($group, $user, TRUE, $permissions, $cacheable_metadata);
    }

    if (!$skip_alter && !isset($post_alter_cache[$operation])) {
      // Let modules alter the permissions. So we get the original ones, and
      // pass them along to the implementing modules.
      $alterable_permissions = static::getPermissionsCache($group, $user, TRUE);
      $context = array(
        'operation' => $operation,
        'group' => $group,
        'user' => $user,
      );
      \Drupal::moduleHandler()->alter('og_user_access', $alterable_permissions, $cacheable_metadata, $context);

      static::setPermissionCache($group, $user, FALSE, $alterable_permissions, $cacheable_metadata);
    }

    $altered_permissions = static::getPermissionsCache($group, $user, FALSE);

    $user_is_group_admin = !empty($altered_permissions['permissions'][static::ADMINISTER_GROUP_PERMISSION]);
    if (($user_is_group_admin && !$ignore_admin) || !empty($altered_permissions['permissions'][$operation])) {
      // User is a group admin, and we do not ignore this special permission
      // that grants access to all the group permissions.
      return AccessResult::allowed()->addCacheableDependency($altered_permissions['cacheable_metadata']);
    }

    return AccessResult::forbidden()->addCacheableDependency($cacheable_metadata);
  }

  /**
   * Check if a user has access to a permission on a certain entity context.
   *
   * @param string $operation
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   (optional) The user object. If empty the current user will be used.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object.
   */
  public static function userAccessEntity($operation, EntityInterface $entity, AccountInterface $user = NULL) {
    $result = AccessResult::neutral();

    // Entity isn't saved yet.
    if ($entity->isNew()) {
      return $result->addCacheableDependency($entity);
    }

    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $bundle = $entity->bundle();

    if (Og::isGroup($entity_type_id, $bundle)) {
      $user_access = static::userAccess($entity, $operation, $user);
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

    // @TODO: add caching on Og::isGroupContent.
    $is_group_content = Og::isGroupContent($entity_type_id, $bundle);
    $cache_tags = $entity_type->getListCacheTags();
    if ($is_group_content && $entity_groups = Og::getEntityGroups($entity)) {
      $forbidden = AccessResult::forbidden()->addCacheTags($cache_tags);
      foreach ($entity_groups as $groups) {
        foreach ($groups as $group) {
          $user_access = static::userAccess($group, $operation, $user);
          if (!$user_access->isForbidden()) {
            //this covers allowed and neutral
            return $user_access->addCacheTags($cache_tags);
          }
          else {
            $forbidden->inheritCacheability($user_access);
          }
        }
      }
      return $forbidden;
    }
    if ($is_group_content) {
      $result->addCacheTags($cache_tags);
    }

    // Either the user didn't have permission, or the entity might be an
    // orphaned group content.
    return $result;
  }

  /**
   * Set the permissions in the static cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object.
   * @param bool $pre_alter $type
   *   Determines if the type of permissions is pre-alter or post-alter.
   * @param array $permissions
   *   Array of permissions to set.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $cacheable_metadata
   *   A cacheable metadata object.
   */
  protected static function setPermissionCache(EntityInterface $group, AccountInterface $user, $pre_alter, array $permissions, RefinableCacheableDependencyInterface $cacheable_metadata) {
    $entity_type_id = $group->getEntityTypeId();
    $group_id = $group->id();
    $user_id = $user->id();
    $type = $pre_alter ? 'pre_alter' : 'post_alter';

    static::$permissionsCache[$entity_type_id][$group_id][$user_id][$type] = [
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
   * @param bool $pre_alter $type
   *   Determines if the type of permissions is pre-alter or post-alter.
   *
   * @return array
   *   Array of permissions if cached, or an empty array.
   */
  protected static function getPermissionsCache(EntityInterface $group, AccountInterface $user, $pre_alter) {
    $entity_type_id = $group->getEntityTypeId();
    $group_id = $group->id();
    $user_id = $user->id();
    $type = $pre_alter ? 'pre_alter' : 'post_alter';

    return isset(static::$permissionsCache[$entity_type_id][$group_id][$user_id][$type]) ?
      static::$permissionsCache[$entity_type_id][$group_id][$user_id][$type] :
      [];
  }

  /**
   * Resets the static cache.
   */
  public static function reset() {
    static::$permissionsCache = ['pre_alter' => [], 'post_alter' => []];
  }

}
