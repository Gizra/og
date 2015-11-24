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
   * @param $group_type
   *   The entity type of the group.
   * @param $group_id
   *   The entity ID of the group.
   * @param string $operation
   *   The entity operation being checked for.
   * @param $account
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
  public static function userAccess($group_type, $group_id, $operation, $account = NULL, $skip_alter = FALSE, $ignore_admin = FALSE) {
    $perm = &drupal_static(__FUNCTION__, []);
    // Mark the group ID and permissions that invoked an alter.
    $perm_alter = &drupal_static(__FUNCTION__ . '_alter', []);

    if (!Og::isGroup($group_type, $group_id)) {
      // Not a group.
      return NULL;
    }


    if (empty($account)) {
      $account = clone \Drupal::currentUser()->getAccount();
    }

    $account_id = $account->id();

    // User #1 has all privileges.
    if ($account_id == 1) {
      return TRUE;
    }

    // Administer group permission.
    if (!$ignore_admin && $account->hasPermission(static::ADMINISTER_GROUP_PERMISSION)) {
      return TRUE;
    }

    // Group manager has all privileges (if variable is TRUE).
    if (!empty($account_id) && \Drupal::config('og.settings')->get('group_manager_full_access')) {
      $group = entity_load($group_type, $group_id);

      if (($group instanceof EntityOwnerInterface) && ($group->getOwnerId() == $account_id)) {
        return TRUE;
      }
    }

    $identifier = $group_type . ':' . $group_id;

    // To reduce the number of SQL queries, we cache the user's permissions
    // in a static variable.
    if (!isset($perm[$identifier][$account_id])) {
      $perms = array();

      if ($roles = og_get_user_roles($group_type, $group_id, $account_id)) {
        // Member might not have roles if they are blocked.
        // A pending member is treated as a non-member.
        $role_permissions = og_role_permissions($roles);

        foreach ($role_permissions as $one_role) {
          $perms += $one_role;
        }
      }

      $perm[$identifier][$account_id] = $perms;
    }

    if (!$skip_alter && empty($perm_alter[$identifier][$account_id][$operation])) {
      // Let modules alter the permissions. since $perm is static we create
      // a clone of it.
      $group = !empty($group) ? $group : entity_load($group_type, $group_id);
      $temp_perm = $perm[$identifier][$account_id];
      $context = array(
        'operation' => $operation,
        'group_type' => $group_type,
        'group' => $group,
        'account' => $account,
      );

      drupal_alter('og_user_access', $temp_perm, $context);

      // Re-assign the altered permissions.
      $perm[$identifier][$account_id] = $temp_perm;

      // Make sure alter isn't called for the same permissions.
      $perm_alter[$identifier][$account_id][$operation] = TRUE;
    }

    return !empty($perm[$identifier][$account_id][$operation]) || (!empty($perm[$identifier][$account_id][static::ADMINISTER_GROUP_PERMISSION]) && !$ignore_admin);
  }

  /**
   * Check if a user has access to a permission on a certain entity context.
   *
   * @param string $operation
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object, or the entity ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user object. If empty the current user will be used.
   *
   * @return bool
   *   Returns TRUE if the user has access to the permission, otherwise FALSE, or
   *   if the entity is not in OG context, function _will return NULL. This allows
   *   a distinction between FALSE - no access, and NULL - no access as no OG
   *   context found.
   */
  public static function userAccessEntity($operation, EntityInterface $entity = NULL, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = clone \Drupal::currentUser()->getAccount();
    }

    // Set the default for the case there is not a group or a group content.
    $result = NULL;

    if (empty($entity)) {
      // $entity might be NULL, so return early.
      // @see field_access().
      return $result;
    }

    // Entity isn't saved yet.
    if ($entity->isNew()) {
      return $result;
    }

    $entity_type_id = $entity->getEntityTypeId();
    $entity_bundle_id = $entity->bundle();

    $is_group = Og::isGroup($entity_type_id, $entity_bundle_id);
    $is_group_content = Og::isGroupContent($entity);

    if ($is_group) {
      if (static::userAccess($entity_type_id, $entity->id(), $operation, $account)) {
        return TRUE;
      }
      else {
        // An entity can be a group and group content in the same time. The group
        // didn't return TRUE, but the user still might have access to the
        // permission in group content context.
        $result = FALSE;
      }
    }

    if ($is_group_content && ($groups = Og::getEntityGroups($entity_type_id, $entity->id()))) {
      foreach ($groups as $group_type => $group_ids) {
        foreach ($group_ids as $group_id) {
          if (static::userAccess($group_type, $group_id, $operation, $account)) {
            return TRUE;
          }
        }
      }

      return FALSE;
    }

    // Either the user didn't have permission, or the entity might be a
    // disabled group or an orphaned group content.
    return $result;
  }

}
