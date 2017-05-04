<?php

namespace Drupal\Tests\og\Kernel\Action;

/**
 * Base class for testing action plugins that change membership roles.
 */
abstract class ChangeOgMembershipActionTestBase extends ActionTestBase {

  /**
   * {@inheritdoc}
   */
  public function accessProvider() {
    return [
      // The super user has access to this action for all member types.
      ['uid1', 'member'],
      ['uid1', 'pending'],
      ['uid1', 'blocked'],
      ['uid1', 'group_administrator'],
      ['uid1', 'group_moderator'],
      // A global administrator has access to this action for all member types.
      ['administrator', 'member'],
      ['administrator', 'pending'],
      ['administrator', 'blocked'],
      ['administrator', 'group_administrator'],
      ['administrator', 'group_moderator'],
      // A group administrator has access to this action for all member types.
      ['group_administrator', 'member'],
      ['group_administrator', 'pending'],
      ['group_administrator', 'blocked'],
      ['group_administrator', 'group_administrator'],
      ['group_administrator', 'group_moderator'],
      // A group moderator has access to this action for all member types.
      ['group_administrator', 'member'],
      ['group_administrator', 'pending'],
      ['group_administrator', 'blocked'],
      ['group_administrator', 'group_administrator'],
      ['group_administrator', 'group_moderator'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function noAccessProvider() {
    return [
      // An anonymous user doesn't have access to this action.
      ['anonymous', 'member'],
      ['anonymous', 'pending'],
      ['anonymous', 'blocked'],
      ['anonymous', 'group_administrator'],
      ['anonymous', 'group_moderator'],
      // A normal authenticated user doesn't have access.
      ['authenticated', 'member'],
      ['authenticated', 'pending'],
      ['authenticated', 'blocked'],
      ['authenticated', 'group_administrator'],
      ['authenticated', 'group_moderator'],
      // A normal group member doesn't have access.
      ['member', 'member'],
      ['member', 'pending'],
      ['member', 'blocked'],
      ['member', 'group_administrator'],
      ['member', 'group_moderator'],
      // A pending group member doesn't have access.
      ['pending', 'member'],
      ['pending', 'pending'],
      ['pending', 'blocked'],
      ['pending', 'group_administrator'],
      ['pending', 'group_moderator'],
      // A blocked group member doesn't have access.
      ['blocked', 'member'],
      ['blocked', 'pending'],
      ['blocked', 'blocked'],
      ['blocked', 'group_administrator'],
      ['blocked', 'group_moderator'],
    ];
  }

}
