<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\og\OgAccess;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessTest extends OgAccessTestBase {

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessNotAGroup($operation) {
    $this->groupManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(FALSE);
    $user_access = $this->ogAccess->userAccess($this->group, $operation);
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testAccessByOperation($operation) {
    $user_access = $this->ogAccess->userAccess($this->group, $operation, $this->user->reveal());

    // We populate the allowed permissions cache in
    // OgAccessTestBase::setup().
    $condition = $operation == 'update group' ? $user_access->isAllowed() : $user_access->isForbidden();

    $this->assertTrue($condition);
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessUser1($operation) {
    $this->user->id()->willReturn(1);
    $user_access = $this->ogAccess->userAccess($this->group, $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessAdminPermission($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_access = $this->ogAccess->userAccess($this->group, $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessOwner($operation) {
    $this->config->get('group_manager_full_access')->willReturn(TRUE);
    $user_access = $this->ogAccess->userAccess($this->groupEntity(TRUE)->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

}
