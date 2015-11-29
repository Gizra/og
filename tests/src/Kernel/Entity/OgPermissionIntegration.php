<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\OgPermissionIntegration.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;

/**
 * Test OG permission mechanism.
 *
 * @group og
 */
class OgPermissionIntegration extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * Test the list of permissions.
   */
  public function testAnonymousUserAccess() {
    $permissions = array_keys(Og::permissionHandler()->getPermissions());
    $this->assertEquals(['administer group', 'update group'], $permissions, 'The permission handler return the expected permissions.');
  }

}
