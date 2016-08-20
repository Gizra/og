<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\og\OgAccess;

/**
 * Tests hook implementation of OG related access.
 *
 * @group og
 */
class OgAccessHookTest extends OgAccessEntityTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Since this is a unit test, we don't enable the module. However, we test
    // a hook implementation inside the module so include the module manually.
    include_once __DIR__ . '/../../../og.module';
  }

  /**
   * Tests that an entity which is not a group or group content is ignored.
   *
   * @dataProvider permissionsProvider
   */
  public function testNotContentEntity($operation) {
    $entity = $this->prophesize(EntityInterface::class);
    $access = og_entity_access($entity->reveal(), $operation, $this->user->reveal());

    // An entity which is not a group or group content should always return
    // neutral, since we have no opinion over it.
    $this->assertTrue($access->isNeutral());
  }

  /**
   * Tests that an administrator with 'administer group' permission has access.
   *
   * @dataProvider permissionsProvider
   */
  public function testGetEntityGroups($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_entity_access = og_entity_access($this->groupContentEntity->reveal(), $operation, $this->user->reveal());

    // @todo This is strange, 'view' is not part of the operations supplied by
    //   ::permissionsProvider(). And why would a group administrator be allowed
    //   access to all operations, except 'view'? Shouldn't this also return
    //   'allowed'?
    if ($operation == 'view') {
      $this->assertTrue($user_entity_access->isNeutral());
    }
    else {
      $this->assertTrue($user_entity_access->isAllowed());
    }
  }

}
