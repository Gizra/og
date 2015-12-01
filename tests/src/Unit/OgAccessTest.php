<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\og\OgAccess;
use Drupal\Tests\UnitTestCase;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccessTest
 */
class OgAccessTest extends UnitTestCase {

  /**
   * @covers ::getPermissionsCache
   * @covers ::setPermissionsCache
   */
  public function testPermissionsCache() {
    $expected = ['pre_alter' => [], 'post_alter' => []];

    $permissions = OgAccess::getPermissionsCache($group, $user, TRUE);
    $this->assertSame([], $permissions);

    $permissions = OgAccess::getPermissionsCache($group, $user, FALSE);
    $this->assertSame([], $permissions);

    $new_permissions = [
      'pre_alter' => [
        'foo' => TRUE,
      ],
      'post_alter' => [
        'foo' => FALSE,
      ],
    ];

    OgAccess::setPermissionCache($group, $user, TRUE, $new_permissions['pre_alter']);
    $permissions = OgAccess::getPermissionsCache($group, $user, TRUE);
    $this->assertSame($new_permissions['pre_alter'], $permissions);

    OgAccess::setPermissionCache($group, $user, TRUE, $new_permissions['post_alter']);
    $permissions = OgAccess::getPermissionsCache($group, $user, FALSE);
    $this->assertSame($new_permissions['post_alter'], $permissions);

    OgAccess::reset();
    $permissions = OgAccess::getPermissionsCache($group, $user, TRUE);
    $this->assertSame([], $permissions);
  }



}
