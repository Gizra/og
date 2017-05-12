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
    $configuration = !empty($default_role_name) ? ['role_name' => $default_role_name] : [];
    $plugin = $this->getPlugin($configuration);
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
      ['administrator', 'member'],
      ['administrator', 'blocked'],
      ['administrator', 'group_administrator'],
      ['administrator', 'group_moderator'],
      ['group_administrator', 'member'],
      ['group_administrator', 'blocked'],
      ['group_administrator', 'group_administrator'],
      ['group_administrator', 'group_moderator'],
      ['group_moderator', 'member'],
      ['group_moderator', 'blocked'],
      ['group_moderator', 'group_administrator'],
      ['group_moderator', 'group_moderator'],
      ['anonymous', 'member'],
      ['anonymous', 'pending'],
      ['anonymous', 'blocked'],
      ['anonymous', 'group_administrator'],
      ['anonymous', 'group_moderator'],
      ['authenticated', 'member'],
      ['authenticated', 'pending'],
      ['authenticated', 'blocked'],
      ['authenticated', 'group_administrator'],
      ['authenticated', 'group_moderator'],
      ['member', 'member'],
      ['member', 'pending'],
      ['member', 'blocked'],
      ['member', 'group_administrator'],
      ['member', 'group_moderator'],
      ['pending', 'member'],
      ['pending', 'pending'],
      ['pending', 'blocked'],
      ['pending', 'group_administrator'],
      ['pending', 'group_moderator'],
      ['blocked', 'member'],
      ['blocked', 'pending'],
      ['blocked', 'blocked'],
      ['blocked', 'group_administrator'],
      ['blocked', 'group_moderator'],
    ];
  }

}
