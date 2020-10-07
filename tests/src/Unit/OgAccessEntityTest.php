<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Unit;

/**
 * OG Access entity tests.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\OgAccess
 */
class OgAccessEntityTest extends OgAccessEntityTestBase {

  /**
   * Tests access to an entity by different permissions.
   *
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider permissionsProvider
   */
  public function testAccessByPermission($permission) {
    $this->membershipManager->getGroups($this->groupContentEntity->reveal())->willReturn([$this->entityTypeId => [$this->group]]);

    $user_access = $this->ogAccess->userAccessEntity($permission, $this->groupContentEntity->reveal(), $this->user->reveal());

    // We populate the allowed permissions cache in
    // OgAccessEntityTestBase::setup().
    $condition = $permission == 'update group' ? $user_access->isAllowed() : $user_access->isNeutral();
    $this->assertTrue($condition);
  }

  /**
   * Tests access to an entity by different permissions, by an admin member.
   *
   * @coversDefaultmethod ::userAccessEntity
   * @dataProvider permissionsProvider
   */
  public function testAccessByPermissionAdmin($permission) {
    $this->membershipManager->getGroups($this->groupContentEntity->reveal())->willReturn([$this->entityTypeId => [$this->group]]);

    $this->user->hasPermission('administer organic groups')->willReturn(TRUE);
    $user_entity_access = $this->ogAccess->userAccessEntity($permission, $this->groupContentEntity->reveal(), $this->user->reveal());
    $this->assertTrue($user_entity_access->isAllowed());
  }

}
