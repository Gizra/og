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
   * The required default role type for non-members.
   */
  const ROLE_TYPE_ANONYMOUS = 'non-member';

  /**
   * The required default role type for members.
   */
  const ROLE_TYPE_AUTHENTICATED = 'member';

  /**
   * The standard, optional, role type.
   */
  const ROLE_TYPE_STANDARD = 'standard';

}
