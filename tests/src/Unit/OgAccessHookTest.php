<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessHookTest.
 */

namespace Drupal\Tests\og\Unit;
use Drupal\Core\Entity\EntityInterface;
use Drupal\og\OgAccess;

/**
 * @group og
 */
class OgAccessHookTest extends OgAccessEntityTestBase {

  public function setUp() {
    parent::setUp();
    // Since this is a unit test, we don't enable the module. However, we test
    // a hook implementation inside the module so include the module manually.
    include_once __DIR__ . '/../../../og.module';
  }

  /**
   * @dataProvider operationProvider
   */
  public function testNotContentEntity($operation) {
    $entity = $this->prophesize(EntityInterface::class);
    $access = og_entity_access($entity->reveal(), $operation, $this->user->reveal());
    $this->assertTrue($access->isNeutral());
  }

  /**
   * @dataProvider operationProvider
   */
  public function testGetEntityGroups($operation) {
    $this->user->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION)->willReturn(TRUE);
    $user_entity_access = og_entity_access($this->entity->reveal(), $operation, $this->user->reveal());
    if ($operation == 'view') {
      $this->assertTrue($user_entity_access->isNeutral());
    }
    else {
      $this->assertTrue($user_entity_access->isAllowed());
    }
  }
}
