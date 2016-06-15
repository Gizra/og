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
   * Returns default properties for the given default OG role name.
   *
   * These are the properties used to create the two roles that are required by
   * every group: the 'member' and 'non-member' roles.
   *
   * @param string $default_role_name
   *   The name of the default role for which to return the properties. Can be
   *   either OgRoleInterface::ANONYMOUS or OgRoleInterface::AUTHENTICATED.
   *
   * @return array
   *   An array of properties, keyed by OG role.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid default role name is passed.
   *
   * @see \Drupal\og\Entity\OgRole::getDefaultRoles()
   */
  public static function getDefaultRoleProperties($default_role_name);

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
