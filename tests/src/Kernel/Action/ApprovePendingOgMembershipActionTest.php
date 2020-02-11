<?php

namespace Drupal\Tests\og\Kernel\Action;

use Drupal\og\OgMembershipInterface;

/**
 * Tests the ApprovePendingOgMembership action plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Action\ApprovePendingOgMembership
 */
class ApprovePendingOgMembershipActionTest extends ActionTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_membership_approve_pending_action';

  /**
   * Checks if the action can be performed correctly.
   *
   * @param string $membership
   *   The membership on which to perform the action.
   *
   * @covers ::execute
   * @dataProvider executeProvider
   */
  public function testExecute($membership = NULL) {
    $membership = $this->memberships[$membership];
    /** @var \Drupal\og\Plugin\Action\AddSingleOgMembershipRole $plugin */
    $plugin = $this->getPlugin();
    $plugin->execute($membership);

    $this->assertEquals(OgMembershipInterface::STATE_ACTIVE, $membership->getState());
  }

  /**
   * {@inheritdoc}
   */
  public function executeProvider() {
    return [
      ['pending'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function accessProvider() {
    return [
      // Access should only be provided if the membership is in pending state
      // and the user executing the action is a privileged user.
      ['uid1', 'pending'],
      ['administrator', 'pending'],
      ['group_administrator', 'pending'],
      ['group_moderator', 'pending'],
      ['group_owner', 'pending', TRUE],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function noAccessProvider() {
    return [
      // Access is denied to users that are not privileged, and if the
      // membership is in any state other than 'pending'.
      ['uid1', 'member'],
      ['uid1', 'blocked'],
      ['uid1', 'group_administrator'],
      ['uid1', 'group_moderator'],
      ['uid1', 'group_owner'],
      ['administrator', 'member'],
      ['administrator', 'blocked'],
      ['administrator', 'group_administrator'],
      ['administrator', 'group_moderator'],
      ['administrator', 'group_owner'],
      ['group_administrator', 'member'],
      ['group_administrator', 'blocked'],
      ['group_administrator', 'group_administrator'],
      ['group_administrator', 'group_moderator'],
      ['group_administrator', 'group_owner'],
      ['group_moderator', 'member'],
      ['group_moderator', 'blocked'],
      ['group_moderator', 'group_administrator'],
      ['group_moderator', 'group_moderator'],
      ['group_moderator', 'group_owner'],
      ['group_owner', 'member', TRUE],
      ['group_owner', 'blocked', TRUE],
      ['group_owner', 'group_administrator', TRUE],
      ['group_owner', 'group_moderator', TRUE],
      ['group_owner', 'group_owner', TRUE],
      ['group_owner', 'member', FALSE],
      ['group_owner', 'pending', FALSE],
      ['group_owner', 'blocked', FALSE],
      ['group_owner', 'group_administrator', FALSE],
      ['group_owner', 'group_moderator', FALSE],
      ['group_owner', 'group_owner', FALSE],
      ['anonymous', 'member'],
      ['anonymous', 'pending'],
      ['anonymous', 'blocked'],
      ['anonymous', 'group_administrator'],
      ['anonymous', 'group_moderator'],
      ['anonymous', 'group_owner'],
      ['authenticated', 'member'],
      ['authenticated', 'pending'],
      ['authenticated', 'blocked'],
      ['authenticated', 'group_administrator'],
      ['authenticated', 'group_moderator'],
      ['authenticated', 'group_owner'],
      ['member', 'member'],
      ['member', 'pending'],
      ['member', 'blocked'],
      ['member', 'group_administrator'],
      ['member', 'group_moderator'],
      ['member', 'group_owner'],
      ['pending', 'member'],
      ['pending', 'pending'],
      ['pending', 'blocked'],
      ['pending', 'group_administrator'],
      ['pending', 'group_moderator'],
      ['pending', 'group_owner'],
      ['blocked', 'member'],
      ['blocked', 'pending'],
      ['blocked', 'blocked'],
      ['blocked', 'group_administrator'],
      ['blocked', 'group_moderator'],
      ['blocked', 'group_owner'],
    ];
  }

}
