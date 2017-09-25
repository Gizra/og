<?php

namespace Drupal\Tests\og\Kernel\Action;

use Drupal\og\Entity\OgRole;

/**
 * Tests the RemoveSingleOgMembershipRole action plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Action\RemoveSingleOgMembershipRole
 */
class RemoveSingleOgMembershipRoleActionTest extends ChangeOgMembershipActionTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_membership_remove_single_role_action';

  /**
   * Checks if the action can be performed correctly.
   *
   * @param string $membership
   *   The membership on which to perform the action.
   * @param string|null $default_role_name
   *   The name of the role that is used by default by the action plugin, if no
   *   other role has been configured through the UI.
   * @param string $expected_role_name
   *   The name of the role that is expected to be removed from the membership
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
    $this->assertFalse($has_role);
  }

  /**
   * Data provider for testExecute().
   */
  public function executeProvider() {
    return [
      // If no default role is passed, the plugin should default to the first
      // available role (administrator).
      ['group_administrator', NULL, 'administrator'],
      ['group_administrator', 'administrator', 'administrator'],
      ['group_administrator', 'moderator', 'moderator'],
      ['group_moderator', NULL, 'administrator'],
      ['group_moderator', 'administrator', 'administrator'],
      ['group_moderator', 'moderator', 'moderator'],
    ];
  }

}
