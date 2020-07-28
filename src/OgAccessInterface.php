<?php

declare(strict_types = 1);

namespace Drupal\og;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for classes that handle access checks in Organic Groups.
 */
interface OgAccessInterface {

  /**
   * Determines whether a user has a certain permission in a given group.
   *
   * The following conditions will result in a positive result:
   * - The user is the global super user (UID 1).
   * - The user has the global permission to administer all organic groups.
   * - The user is the owner of the group, and OG has been configured to allow
   *   full access to the group owner.
   * - The user has the role of administrator in the group.
   * - The user has a role in the group that specifically grants the permission.
   * - The user is not a member of the group, and the permission has been
   *   granted to non-members.
   *
   * The access result can be altered by implementing hook_og_user_access().
   *
   * All access checks in OG should go through this function. This way we
   * guarantee consistent behavior, and ensure that the superuser and group
   * administrators can perform all actions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param string $permission
   *   The permission being checked.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   (optional) The user to check. Defaults to the current user.
   * @param bool $skip_alter
   *   (optional) If TRUE then user access will not be sent to other modules
   *   using drupal_alter(). This can be used by modules implementing
   *   hook_og_user_access_alter() that still want to use og_user_access(), but
   *   without causing a recursion. Defaults to FALSE.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result object.
   */
  public function userAccess(EntityInterface $group, string $permission, AccountInterface $user = NULL, bool $skip_alter = FALSE): AccessResultInterface;

  /**
   * Check if a user has access to a permission on a certain entity context.
   *
   * @param string $operation
   *   The operation to perform on the entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   (optional) The user object. If empty the current user will be used.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result object.
   */
  public function userAccessEntity($operation, EntityInterface $entity, AccountInterface $user = NULL): AccessResultInterface;

  /**
   * Checks access for entity operations on group content entities.
   *
   * This checks if the user has permission to perform the requested operation
   * on the given group content entity according to the user's membership status
   * in the given group. There is no formal support for access control on entity
   * operations in core, so the mapping of permissions to operations is provided
   * by PermissionManager::getEntityOperationPermissions().
   *
   * @param string $operation
   *   The entity operation.
   * @param \Drupal\Core\Entity\EntityInterface $group_entity
   *   The group entity, to retrieve the permissions from.
   * @param \Drupal\Core\Entity\EntityInterface $group_content_entity
   *   The group content entity for which access to the entity operation is
   *   requested.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Optional user for which to check access. If omitted, the currently logged
   *   in user will be used.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   *
   * @see \Drupal\og\PermissionManager::getEntityOperationPermissions()
   */
  public function userAccessGroupContentEntityOperation($operation, EntityInterface $group_entity, EntityInterface $group_content_entity, AccountInterface $user = NULL): AccessResultInterface;

  /**
   * Resets the static cache.
   *
   * @deprecated in og:8.x-1.0-alpha6 and is removed from og:8.x-1.0-beta1.
   *   The static cache has been removed and this method no longer serves any
   *   purpose. Any calls to this method can safely be removed.
   * @see https://github.com/Gizra/og/issues/654
   */
  public function reset(): void;

}
