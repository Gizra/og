<?php

/**
 * @file
 * Contains \Drupal\og\OgMembershipInterface.
 */

namespace Drupal\og;

use \Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\og\Entity\OgRole;

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
   * @param mixed $etid
   *   The user's ID or object.
   *
   * @return OgMembershipInterface
   */
  public function setUser($etid);

  /**
   * Sets the membership field name.
   *
   * A group content can have two group reference fields. The field name
   * property helps us to know to which field the membership belongs.
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
   * Sets the group entity type ID.
   *
   * @param mixed $gid
   *   The group entity type ID or name.
   *
   * @return OgMembershipInterface
   */
  public function setEntityId($gid);

  /**
   * Gets the group entity type ID.
   *
   * @return mixed
   */
  public function getEntityId();

  /**
   * Sets the group entity type ID.
   *
   * @param mixed $groupType
   *
   * @return OgMembershipInterface
   */
  public function setEntityType($groupType);

  /**
   * @return mixed
   */
  public function getGroupEntityType();

  /**
   * Sets the membership state.
   *
   * @param bool $state
   *
   * @return OgMembershipInterface
   */
  public function setState($state);

  /**
   * Gets the membership state.
   *
   * @return mixed
   */
  public function getState();

  /**
   * Gets the membership type
   *
   * @return string
   *
   */
  public function getType();

  /**
   * Sets the group's role's for the current user group membership's.
   *
   * @param $role_ids
   *   List of og roles ids.
   *
   * @return OgMembershipInterface
   */
  public function setRoles($role_ids);

  /**
   * Adding a role to the user membership.
   *
   * @param $role_id
   *   The OG role ID.
   *
   * @return OgMembershipInterface
   */
  public function addRole($role_id);

  /**
   * Revoking a role from the OG membership.
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
   */
  public function getGroup();

}
