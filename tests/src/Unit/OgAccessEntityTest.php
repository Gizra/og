<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\OgAccess;

/**
 * OG Access entity tests.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessEntityTest extends OgAccessEntityTestBase {

  /**
   * Tests access to an entity by different operations.
   *
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider permissionsProvider
   */
  public function testAccessByOperation($operation) {
    $this->membershipManager->getGroups($this->groupContentEntity->reveal())->willReturn([$this->entityTypeId => [$this->group]]);

    $user_access = $this->ogAccess->userAccessEntity($operation, $this->groupContentEntity->reveal(), $this->user->reveal());

    // We populate the allowed permissions cache in
    // OgAccessEntityTestBase::setup().
    $condition = $operation == 'update group' ? $user_access->isAllowed() : $user_access->isForbidden();
    $this->assertTrue($condition);
  }

  /**
   * Tests access to an entity by different operations, by an admin member.
   *
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider permissionsProvider
   */
  public function testAccessByOperationAdmin($operation) {
    $this->membershipManager->getGroups($this->groupContentEntity->reveal())->willReturn([$this->entityTypeId => [$this->group]]);

    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_entity_access = $this->ogAccess->userAccessEntity($operation, $this->groupContentEntity->reveal(), $this->user->reveal());
    $this->assertTrue($user_entity_access->isAllowed());
  }

}
