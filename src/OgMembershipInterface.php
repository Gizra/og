<?php

/**
 * @file
 * Contains \Drupal\og\OgMembershipInterface.
 */

namespace Drupal\og;

/**
 * Provides an interface for OG memberships.
 * @todo Provide some actual helpful documentation.
 */
interface OgMembershipInterface {

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

}
