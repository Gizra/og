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
   * Determines whether a user has a group permission in a given group.
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
   * - The access result can be altered (granted or denied) by implementing
   *   hook_og_user_access().
   *
   * For access to be granted, at least one of the above checks should grant
   * access, and none of the checks should deny access. A neutral result is
   * returned only if all checks are neutral.
   *
   * All access checks in OG should go through this function. This way we
   * guarantee consistent behavior, and ensure that the superuser and group
   * administrators can perform all actions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param string $permission
   *   The name of the OG permission being checked. This includes both group
   *   level permissions such as 'subscribe without approval' and group content
   *   entity operation permissions such as 'edit own article content'.
   * @param \Drupal\Core\Session\AccountInterface|null $user
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
  public function userAccess(EntityInterface $group, string $permission, ?AccountInterface $user = NULL, bool $skip_alter = FALSE): AccessResultInterface;

  /**
   * Determines whether a user has a group permission in a given entity.
   *
   * This does an exhaustive, but slow, check to discover whether access can be
   * granted and works both on groups and group content. It will iterate over
   * all groups that are associated with the entity and do a permission check on
   * each group. If a passed in entity is both a group and group content, it
   * will return a positive result if the user has the requested permission in
   * either the entity itself or its parent group(s).
   *
   * For access to be granted, at least one of the groups with which the entity
   * is associated should allow access, and none of the associated groups should
   * deny access. Access is denied if one or more groups deny access, regardless
   * of how many groups grant access. A neutral result is returned if the passed
   * in entity is not associated with any groups, or if all the associated
   * groups return a neutral result.
   *
   * In case you know the specific group you want to check access for then it is
   * recommended to use the faster ::userAccess().
   *
   * @param string $permission
   *   The name of the OG permission being checked. This includes both group
   *   level permissions such as 'subscribe without approval' and group content
   *   entity operation permissions such as 'edit own article content'.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object. This can be either a group or group content entity.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   (optional) The user object. If empty the current user will be used.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result object.
   *
   * @see \Drupal\og\userAccess();
   */
  public function userAccessEntity(string $permission, EntityInterface $entity, ?AccountInterface $user = NULL): AccessResultInterface;

  /**
   * Checks whether a user can perform an operation on a given entity.
   *
   * This does an exhaustive, but slow, check to discover whether the operation
   * can be performed. It works both with groups and group content entities. It
   * will iterate over all groups that are associated with the entity and check
   * if the user is allowed to perform the entity operation on the group content
   * entity according to their role within each group.
   *
   * For access to be granted, at least one of the groups with which the entity
   * is associated should allow access, and none of the associated groups should
   * deny access. Access is denied if one or more groups deny access, regardless
   * of how many groups grant access. A neutral result is returned if the passed
   * in entity is not associated with any groups, or if all the associated
   * groups return a neutral result.
   *
   * If a passed in entity is both a group and group content, it will first
   * check if the user has permission to perform the operation on the entity
   * itself. Only if this returns a neutral result the access check will be
   * performed on its parent group(s).
   *
   * In case you know the specific group you want to check access for then it is
   * recommended to use the faster ::userAccessGroupContentEntityOperation().
   *
   * @param string $operation
   *   The entity operation, such as "create", "update" or "delete".
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object. This can be either a group or group content entity.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   (optional) The user object. If empty the current user will be used.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result object.
   *
   * @see \Drupal\og\userAccessGroupContentEntityOperation();
   */
  public function userAccessEntityOperation(string $operation, EntityInterface $entity, ?AccountInterface $user = NULL): AccessResultInterface;

  /**
   * Checks access for entity operations on group content in a specific group.
   *
   * This checks if the user has permission to perform the requested operation
   * on the given group content entity according to the user's membership status
   * in the given group. It also passes through ::userAccess() to check for any'
   * of the special cases, such as being the root user, having global permission
   * to administer all groups, etc.
   *
   * The access result can be altered by implementing an event listener for
   * GroupContentEntityOperationAccessEventInterface::EVENT_NAME.
   *
   * For access to be granted, at least one of the above checks should grant
   * access, and none of the event listeners should deny access. A neutral
   * result is returned only if all checks are neutral or if the passed in
   * entity is not group content.
   *
   * @param string $operation
   *   The entity operation, such as "create", "update" or "delete".
   * @param \Drupal\Core\Entity\EntityInterface $group_entity
   *   The group entity, to retrieve the permissions from.
   * @param \Drupal\Core\Entity\EntityInterface $group_content_entity
   *   The group content entity for which access to the entity operation is
   *   requested.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   Optional user for which to check access. If omitted, the currently logged
   *   in user will be used.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function userAccessGroupContentEntityOperation(string $operation, EntityInterface $group_entity, EntityInterface $group_content_entity, ?AccountInterface $user = NULL): AccessResultInterface;

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
