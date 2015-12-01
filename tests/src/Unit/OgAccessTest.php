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

    $permissions = OgAccess::getPermissionsCache($group, $user)

  }



}
