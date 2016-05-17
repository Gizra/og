<?php

/**
 * @file
 * Contains \Drupal\og\OgMembershipInterface.
 */

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\og\Entity\OgRole;
use Drupal\user\Entity\User;

/**
 * Provides an interface for OG memberships.
 * @todo Provide some actual helpful documentation.
 */
interface OgMembershipInterface extends ContentEntityInterface {

  /**
   * Define active group content states.
   *
   * When a user has this membership state they are considered to be of
   * "member" role.
   */
  const STATE_ACTIVE = 1;

  /**
   * Define pending group content states. The user is subscribed to the group
   * but isn't an active member yet.
   *
   * When a user has this membership state they are considered to be of
   * "non-member" role.
   */
  const STATE_PENDING = 2;

  /**
   * Define blocked group content states. The user is rejected from the group.
   *
   * When a user has this membership state they are denied access to any
   * group related action. This state, however, does not prevent user to
   * access a group or group content node.
   */
  const STATE_BLOCKED = 3;

  /**
   * The default group membership type that is the bundle of group membership.
   */
  const TYPE_DEFAULT = 'og_membership_type_default';

  /**
   * The name of the user's request field in the default group membership type.
   */
  const REQUEST_FIELD = 'og_membership_request';

  /**
   * Gets the membership creation timestamp.
   *
   * @return int
   *   The membership creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the membership creation timestamp.
   *
   * @param int $timestamp
   *   The membership creation timestamp
   *
   * @return OgMembershipInterface
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets the membership's owner.
   *
   * @param mixed $etid
   *   The user's ID or object.
   *
   * @return OgMembershipInterface
   */
  public function setUser($etid);

  /**
   * Gets the membership's owner.
   * 
   * @return User
   *   The user object.
   */
  public function getUser();

  /**
   * Sets the membership field name.
   *
   * A user can have two group reference fields. The field name property helps
   * us to know to which field the membership belongs.
   *
   * @param string $fieldName
   *   The group reference field name.
   *
   * @return OgMembershipInterface
   */
  public function setFieldName($fieldName);

  /**
   * Gets the membership field name.
   *
   * @return string
   *   The group reference field name.
   */
  public function getFieldName();

  /**
   * Sets the group entity ID.
   *
   * @param mixed $gid
   *   The group entity ID.
   *
   * @return OgMembershipInterface
   */
  public function setEntityId($gid);

  /**
   * Gets the group entity ID.
   *
   * @return integer
   *   The entity identifier.
   */
  public function getEntityId();

  /**
   * Sets the group entity type ID.
   *
   * @param mixed $groupType
   *   The group entity type ID or object.
   *
   * @return OgMembershipInterface
   */
  public function setGroupEntityType($groupType);

  /**
   * Gets the group entity type ID.
   *
   * @return string
   *   The group entity type ID.
   */
  public function getGroupEntityType();

  /**
   * Sets the membership state.
   *
   * @param bool $state
   *   TRUE or FALSE.
   *
   * @return OgMembershipInterface
   */
  public function setState($state);

  /**
   * Gets the membership state.
   *
   * @return bool
   */
  public function getState();

  /**
   * Gets the membership type.
   *
   * @return string
   *   The bundle of the membership type.
   */
  public function getType();

  /**
   * Sets the group's roles for the current user group membership.
   *
   * @param $role_ids
   *   List of OG roles ids.
   *
   * @return OgMembershipInterface
   */
  public function setRoles($role_ids);

  /**
   * Adds a role to the user membership.
   *
   * @param $role_id
   *   The OG role ID.
   *
   * @return OgMembershipInterface
   */
  public function addRole($role_id);

  /**
   * Revokes a role from the OG membership.
   *
   * @param $role_id
   *   The OG role ID.
   *
   * @return OgMembershipInterface
   */
  public function revokeRole($role_id);

  /**
   * Gets all the referenced OG roles.
   *
   * @return OgRole[]
   *   List of OG roles the user own for the current membership instance.
   */
  public function getRoles();

  /**
   * Gets list of OG role IDs.
   *
   * @return array
   *   List of OG roles ids.
   */
  public function getRolesIds();

  /**
   * Checks if the user has a permission inside the group.
   *
   * @param $permission
   *   The name of the permission.
   *
   * @return bool
   */
  public function hasPermission($permission);

  /**
   * Gets the group object.
   *
   * @return EntityInterface
   *   The group object which the membership reference to.
   */
  public function getGroup();

}
