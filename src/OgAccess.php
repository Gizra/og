<?php

/**
 * @file
 * Contains \Drupal\og\OgAccess.
 */

namespace Drupal\og;

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
   * Allow access to the entity.
   *
   * @var string
   */
  const ALLOW_ACCESS = TRUE;

  /**
   * Deny access to the entity.
   *
   * @var string
   */
  const DENY_ACCESS = FALSE;

  /**
   * Entity is not in OG context, so we are neutral regarding access to it.
   *
   * @var string
   */
  const NEUTRAL = NULL;



  /**
   * Determines whether a user has a given privilege.
   *
   * All permission checks in OG should go through this function. This
   * way, we guarantee consistent behavior, and ensure that the superuser
   * and group administrators can perform all actions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group_entity
   *   The group entity.
   * @param string $operation
   *   The entity operation being checked for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account to check. Defaults to the current user.
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
   * @return bool
   *   TRUE or FALSE if the current user has the requested permission.
   *   NULL, if the given group isn't a valid group.
   */
  public static function userAccess(EntityInterface $group_entity, $operation, AccountInterface $account = NULL, $skip_alter = FALSE, $ignore_admin = FALSE) {
    $group_type_id = $group_entity->getEntityTypeId();
    $bundle = $group_entity->bundle();

    if (!Og::isGroup($group_type_id, $bundle)) {
      // Not a group.
      return static::NEUTRAL;
    }

    $account = $account ?: \Drupal::currentUser()->getAccount();
    $account_id = $account->id();

    // User ID 1 has all privileges.
    if ($account_id == 1) {
      return static::ALLOW_ACCESS;
    }

    // Administer group permission.
    if (!$ignore_admin && $account->hasPermission(static::ADMINISTER_GROUP_PERMISSION)) {
      return static::ALLOW_ACCESS;
    }

    // Group manager has all privileges (if variable is TRUE) and they are
    // authenticated.
    if (\Drupal::config('og.settings')->get('group_manager_full_access')) {
      if (!empty($account_id) && $group_entity instanceof EntityOwnerInterface && $group_entity->getOwnerId() == $account_id) {
        return static::ALLOW_ACCESS;
      }
    }

    $pre_alter_cache = static::getPermissionsCache($group_entity, $account, TRUE);
    $post_alter_cache = static::getPermissionsCache($group_entity, $account, FALSE);

    // To reduce the number of SQL queries, we cache the user's permissions
    // in a static variable.
    if (!$pre_alter_cache) {
      $permissions = array();

      // @todo: Getting permissions from OG Roles will be added here.

      static::setPermissionCache($group_entity, $account, TRUE, $permissions);
    }

    if (!$skip_alter && !isset($post_alter_cache[$operation])) {
      // Let modules alter the permissions. So we get the original ones, and
      // pass them along to the implementing modules.
      // @todo: Check if still needed to do a clone, since the cache is static
      // we don't want it to be altered.
      $alterable_permissions = static::getPermissionsCache($group_entity, $account, TRUE);
      $context = array(
        'operation' => $operation,
        'group_entity' => $group_entity,
        'account' => $account,
      );

      \Drupal::moduleHandler()->alter('og_user_access', $alterable_permissions, $context);

      static::setPermissionCache($group_entity, $account, FALSE, $alterable_permissions);
    }

    $altered_permissions = static::getPermissionsCache($group_entity, $account, TRUE);

    if (!empty($altered_permissions[static::ADMINISTER_GROUP_PERMISSION]) && !$ignore_admin) {
      // User is a group admin, and we do not ignore this special permission
      // that grants access to all the group permissions.
      return static::ALLOW_ACCESS;
    }

    return !empty($altered_permissions[$operation]);
  }

  /**
   * Check if a user has access to a permission on a certain entity context.
   *
   * @param string $operation
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user object. If empty the current user will be used.
   *
   * @return bool|NULL
   *   Returns TRUE if the user has access to the permission, otherwise FALSE, or
   *   if the entity is not in OG context, function _will return NULL. This allows
   *   a distinction between FALSE - no access, and NULL - no access as no OG
   *   context found.
   */
  public static function userAccessEntity($operation, EntityInterface $entity, AccountInterface $account = NULL) {
    // Entity isn't saved yet.
    if ($entity->isNew()) {
      return static::NEUTRAL;
    }

    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $is_group_content = Og::isGroupContent($entity_type, $bundle);

    $result = static::NEUTRAL;

    if (Og::isGroup($entity_type, $bundle)) {
      if (static::userAccess($entity, $operation, $account)) {
        return TRUE;
      }
      else {
        // An entity can be a group and group content in the same time. The
        // group didn't return TRUE, but the user still might have access to the
        // permission in group content context. So instead of retuning a deny
        // here, we set the result, that might change if an access is found.
        $result = static::DENY_ACCESS;
      }
    }

    if ($is_group_content && $result = Og::getEntityGroups($entity_type, $entity->id())) {
      foreach ($result as $groups) {
        foreach ($groups as $group) {
          if (static::userAccess($group,$operation, $account)) {
            return static::ALLOW_ACCESS;
          }
        }
      }

      return static::DENY_ACCESS;
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
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   * @param bool $pre_alter $type
   *   Determines if the type of permissions is pre-alter or post-alter.
   * @param array $permissions
   *   Array of permissions to set.
   */
  public static function setPermissionCache(EntityInterface $group, AccountInterface $account, $pre_alter, array $permissions) {

  }

  /**
   * Get the permissions from the static cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   * @param bool $pre_alter $type
   *   Determines if the type of permissions is pre-alter or post-alter.
   *
   * @return array
   *   Array of permissions if cached, or an empty array.
   */
  public static function getPermissionsCache(EntityInterface $group, AccountInterface $account, $pre_alter) {
    $entity_type_id = $group->getEntityTypeId();
    $group_id = $group->id();
    $account_id = $account->id();
    $type = $pre_alter ? 'pre_alter' : 'post_alter';

    return static::$permissionsCache[$entity_type_id][$group_id][$account_id][$type];
  }

  /**
   * Resets the static cache.
   */
  public static function reset() {
    static::$permissionsCache = ['pre_alter' => [], 'post_alter' => []];
  }

}
