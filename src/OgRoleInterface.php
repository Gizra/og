<?php

/**
 * @file
 * Contains Drupal\og\OgRoleInterface.
 */
namespace Drupal\og;

/**
 * Provides an interface defining an OG user role entity.
 *
 * Class cannot extend RoleInterface due to PHP 5 limitations.
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
  const ADMINISTRATOR = 'administrator';

  /**
   * Role type for required roles.
   *
   * This is intended for the 'non-member' and 'member' roles. These cannot be
   * changed or deleted.
   */
  const ROLE_TYPE_REQUIRED = 'required';

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
   * Returns the role name.
   *
   * @return string
   *   The role name.
   */
  public function getName();

  /**
   * Sets the role name.
   *
   * @param string $name
   *   The role name
   *
   * @return $this
   */
  public function setName($name);

}
