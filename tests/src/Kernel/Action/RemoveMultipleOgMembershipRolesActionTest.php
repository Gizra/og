<?php

namespace Drupal\Tests\og\Kernel\Action;

/**
 * Tests the RemoveMultipleOgMembershipRoles action plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Action\RemoveMultipleOgMembershipRoles
 */
class RemoveMultipleOgMembershipRolesActionTest extends ChangeMultipleOgMembershipRolesActionTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'og_membership_remove_multiple_roles_action';

}
