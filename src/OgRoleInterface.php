<?php

/**
 * @file
 * Contains Drupal\og\OgRoleInterface.
 */
namespace Drupal\og;

/**
 * Provides an interface defining an OG user role entity.
 */
interface OgRoleInterface {

  /**
   * The role name of the group non-member.
   */
  const ANONYMOUS = 'non-member';

  /**
   * The role name of the group member.
   */
  const AUTHENTICATED = 'member';

  /**
   * The role name of the group administrator.
   */
  const ADMINISTRATOR = 'administrator member';

  /**
   * Role type for default roles which should not be changed.
   *
   * This is intended for the 'non-member' and 'member' roles.
   */
  const ROLE_TYPE_IMMUTABLE = 'immutable';

  /**
   * Role type for standard roles that are editable and deletable.
   */
  const ROLE_TYPE_STANDARD = 'standard';

  /**
   * Sets the ID of the role.
   *
   * @param string $id
   *   The machine name of the role.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Returns whether or not a role can be changed.
   *
   * This will return TRUE for all roles except the default roles 'non-member'
   * and 'member'.
   *
   * @return bool
   *   Whether or not the role is mutable.
   */
  public function isMutable();

}
