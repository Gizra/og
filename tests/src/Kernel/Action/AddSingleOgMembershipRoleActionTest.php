<?php

namespace Drupal\Tests\og\Kernel\Action;

use Drupal\og\Entity\OgRole;

/**
 * Tests the AddSingleOgMembershipRole action plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Action\AddSingleOgMembershipRole
 */
class AddSingleOgMembershipRoleActionTest extends ChangeOgMembershipActionTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_membership_add_single_role_action';

  /**
   * Checks if the action can be performed correctly.
   *
   * @param string $membership
   *   The membership on which to perform the action.
   * @param string|null $default_role_name
   *   The name of the role that is used by default by the action plugin, if no
   *   other role has been configured through the UI.
   * @param string $expected_role_name
   *   The name of the role that is expected to be present on the membership
   *   after the action has been executed.
   *
   * @covers ::execute
   * @dataProvider executeProvider
   */
  public function testExecute($membership, $default_role_name = NULL, $expected_role_name = NULL) {
    /** @var \Drupal\og\Plugin\Action\AddSingleOgMembershipRole $plugin */
    $configuration = !empty($default_role_name) ? ['role_name' => $default_role_name] : [];
    $plugin = $this->getPlugin($configuration);
    $plugin->execute($this->memberships[$membership]);

    $has_role = (bool) array_filter($this->memberships[$membership]->getRoles(), function (OgRole $role) use ($expected_role_name) {
      return $role->getName() === $expected_role_name;
    });
    $this->assertTrue($has_role);
  }

  /**
   * Data provider for testExecute().
   */
  public function executeProvider() {
    // It should be possible to add roles to any membership, regardless if they
    // are pending or blocked, or have any other membership.
    return [
      // If no default role is passed, the plugin should default to the first
      // available role (administrator).
      ['member', NULL, 'administrator'],
      ['member', 'administrator', 'administrator'],
      ['member', 'moderator', 'moderator'],
      ['pending', NULL, 'administrator'],
      ['pending', 'administrator', 'administrator'],
      ['pending', 'moderator', 'moderator'],
      ['blocked', NULL, 'administrator'],
      ['blocked', 'administrator', 'administrator'],
      ['blocked', 'moderator', 'moderator'],
      ['group_administrator', NULL, 'administrator'],
      // If an administrator is given the administrator role a second time, the
      // role should be kept intact.
      ['group_administrator', 'administrator', 'administrator'],
      ['group_administrator', 'moderator', 'moderator'],
      // If an administrator is also made a moderator, they should still keep
      // the administrator role.
      ['group_administrator', 'moderator', 'administrator'],
      ['group_moderator', NULL, 'administrator'],
      ['group_moderator', 'administrator', 'administrator'],
      // If a moderator is given the moderator role a second time, the role
      // should be kept intact.
      ['group_moderator', 'moderator', 'moderator'],
      // If a moderator is also made an administrator, they should still keep
      // the moderator role.
      ['group_moderator', 'administrator', 'moderator'],
    ];
  }

}
