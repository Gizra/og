<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessEntity.
 */


namespace Drupal\Tests\og\Unit;

use Drupal\og\OgAccess;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessEntityTest extends OgAccessEntityTestBase {

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testDefaultForbidden($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(FALSE);
    $user_access = OgAccess::userAccessEntity($operation, $this->entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_access->isForbidden());
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testEntityNew($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(TRUE);
    $user_access = OgAccess::userAccessEntity($operation, $group_entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testGetEntityGroups($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_entity_access = OgAccess::userAccessEntity($operation, $this->entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_entity_access->isAllowed());
  }

}
