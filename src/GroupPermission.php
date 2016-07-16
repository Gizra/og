<?php

namespace Drupal\og;

/**
 * A group level permission.
 *
 * This is used for permissions that apply to the group as a whole, such as
 * 'subscribe without approval' and 'administer group'.
 */
class GroupPermission extends Permission {

  /**
   * A list of roles to which this permission can be applied.
   *
   * For example, the 'subscribe' permission only applies to non-members. If
   * left empty, this permission applies to all roles.
   *
   * @var array
   */
  protected $roles = [];

  /**
   * Returns the roles to which this permission can be applied.
   *
   * For example, the 'subscribe' permission only applies to non-members.
   *
   * @return array
   *   An array of roles to which this permission applies. If empty, the
   *   permission applies to all roles.
   */
  public function getApplicableRoles() {
    return $this->get('roles');
  }

  /**
   * Returns the roles to which this permission can be applied.
   *
   * For example, the 'subscribe' permission only applies to non-members.
   *
   * @param array $roles
   *   An array of roles to which this permission applies. If empty, the
   *   permission applies to all roles.
   *
   * @return $this
   */
  public function setApplicableRoles(array $roles) {
    $this->set('roles', $roles);
    return $this;
  }

}
