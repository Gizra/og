<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\OgAccess;

/**
 * Tests access.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessTest extends OgAccessTestBase {

  /**
   * Tests access for a non-group related entity.
   *
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessNotGroup($operation) {
    $this->groupTypeManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(FALSE);
    $user_access = $this->ogAccess->userAccess($this->group, $operation);
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * Tests access to entity.
   *
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
   * Tests access by the super user, which is user ID 1.
   *
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessUser1($operation) {
    $this->user->id()->willReturn(1);
    $user_access = $this->ogAccess->userAccess($this->group, $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * Tests access by a group administrator.
   *
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessAdminPermission($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_access = $this->ogAccess->userAccess($this->group, $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * Tests access by the owner of the entity.
   *
   * @coversDefaultmethod ::userAccess
   * @dataProvider permissionsProvider
   */
  public function testUserAccessOwner($operation) {
    $this->config->get('group_manager_full_access')->willReturn(TRUE);
    $user_access = $this->ogAccess->userAccess($this->groupEntity(TRUE)->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

}
