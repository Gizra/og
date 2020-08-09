<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\Entity\OgRole;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the OgRole config entity.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Entity\OgRole
 */
class OgRoleTest extends UnitTestCase {

  /**
   * Tests getting and setting the admin role through the inherited methods.
   *
   * @param bool $value
   *   A boolean value whether or not the admin role will be set.
   *
   * @covers ::isAdmin
   * @covers ::setIsAdmin
   *
   * @dataProvider booleanProvider
   */
  public function testIsAdmin($value) {
    $role = new OgRole([]);
    $role->setIsAdmin($value);
    $this->assertEquals($value, $role->isAdmin());
  }

  /**
   * Provides boolean data.
   */
  public function booleanProvider() {
    return [[TRUE], [FALSE]];
  }

}
