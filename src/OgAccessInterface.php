<?php

namespace Drupal\og;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for classes that handle access checks in Organic Groups.
 */
interface OgAccessInterface {

  /**
   * Determines whether a user has a given privilege.
   *
   * All permission checks in OG should go through this function. This way we
   * guarantee consistent behavior, and ensure that the superuser and group
   * administrators can perform all actions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param string $operation
   *   The entity operation being checked for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   (optional) The user to check. Defaults to the current user.
   * @param bool $skip_alter
   *   (optional) If TRUE then user access will not be sent to other modules
   *   using drupal_alter(). This can be used by modules implementing
   *   hook_og_user_access_alter() that still want to use og_user_access(), but
   *   without causing a recursion. Defaults to FALSE.
   * @param bool $ignore_admin
   *   (optional) When TRUE the specific permission is checked, ignoring the
   *   "administer group" permission if the user has it. When FALSE, a user
   *   with "administer group" will be granted all permissions.
   *   Defaults to FALSE.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object.
   */
  public function userAccess(EntityInterface $group, $operation, AccountInterface $user = NULL, $skip_alter = FALSE, $ignore_admin = FALSE);

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
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object.
   */
  public function userAccessEntity($operation, EntityInterface $entity, AccountInterface $user = NULL);

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
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   *
   * @see \Drupal\og\PermissionManager::getEntityOperationPermissions()
   */
  public function userAccessGroupContentEntityOperation($operation, EntityInterface $group_entity, EntityInterface $group_content_entity, AccountInterface $user = NULL);

  /**
   * Resets the static cache.
   */
  public function reset();

}
