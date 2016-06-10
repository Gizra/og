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
  public function testAccessByOperation($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(FALSE);

    $user_access = $this->ogAccess->userAccessEntity($operation, $this->groupContentEntity->reveal(), $this->user->reveal());

    // We populate the allowed permissions cache in
    // OgAccessEntityTestBase::setup().
    $condition = $operation == 'update group' ? $user_access->isAllowed() : $user_access->isForbidden();
    $this->assertTrue($condition);
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testEntityNew($operation) {
    $group_entity = $this->groupEntity();
    $group_entity->isNew()->willReturn(TRUE);
    $user_access = $this->ogAccess->userAccessEntity($operation, $group_entity->reveal(), $this->user->reveal());
    $this->assertTrue($user_access->isNeutral());
  }

  /**
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider operationProvider
   */
  public function testGetEntityGroups($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_entity_access = $this->ogAccess->userAccessEntity($operation, $this->groupContentEntity->reveal(), $this->user->reveal());
    $this->assertTrue($user_entity_access->isAllowed());
  }

}
