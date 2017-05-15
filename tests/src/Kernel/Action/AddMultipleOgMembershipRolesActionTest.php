<?php

namespace Drupal\Tests\og\Kernel\Action;

/**
 * Tests the AddMultipleOgMembershipRoles action plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Action\AddMultipleOgMembershipRoles
 */
class AddMultipleOgMembershipRolesActionTest extends ChangeMultipleOgMembershipRolesActionTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_membership_add_multiple_roles_action';

}
