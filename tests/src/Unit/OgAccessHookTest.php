<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgAccessHookTest.
 */

namespace Drupal\Tests\og\Unit;
use Drupal\Core\Entity\EntityInterface;

/**
 * @group og
 */
class OgAccessHookTest extends OgAccessTestBase {

  public function setUp() {
    parent::setUp();
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


}
