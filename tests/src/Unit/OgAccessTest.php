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
   * @dataProvider operationProvider
   */
  public function testUserAccessNotAGroup($operation) {
    $this->groupManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(FALSE);
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation);
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testUserAccessForbiddenByDefault($operation) {
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isForbidden());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testUserAccessUser1($operation) {
    $this->user->id()->willReturn(1);
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testUserAccessAdminPermission($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_access = OgAccess::userAccess($this->groupEntity()->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testUserAccessOwner($operation) {
    $this->config->get('group_manager_full_access')->willReturn(TRUE);
    $user_access = OgAccess::userAccess($this->groupEntity(TRUE)->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

  /**
   * @coversDefaultmethod ::userAccess
   * @dataProvider operationProvider
   */
  public function testUserAccessOgUserAccessAlter($operation) {
    $permissions[OgAccess::ADMINISTER_GROUP_PERMISSION] = TRUE;
    \Drupal::getContainer()->set('module_handler', new OgAccessTestAlter($permissions));
    $group_entity = $this->groupEntity();
    $group_entity->id()->willReturn(mt_rand(5, 10));
    $user_access = OgAccess::userAccess($group_entity->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($user_access->isAllowed());
  }

}

class OgAccessTestAlter {
  public function __construct($data) {
    $this->data = $data;
  }
  public function alter($op, &$data) {
    $data = $this->data;
  }
}
