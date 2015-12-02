<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\GroupManagerTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\og\GroupManager;
use Drupal\Tests\UnitTestCase;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\GroupManager
 */
class GroupManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Config\Config|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configProphecy;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactoryProphecy;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->configProphecy = $this->prophesize('Drupal\Core\Config\Config');
    $this->configFactoryProphecy = $this->prophesize('Drupal\Core\Config\ConfigFactoryInterface');
  }

  /**
   * @covers ::__construct
   */
  public function testInstance() {
    $this->configProphecy->get('groups')
      ->shouldBeCalled();

    // Just creating an instance should not get the 'groups' config key.
    $this->createGroupManager();
  }

  /**
   * @covers ::getAllGroupBundles
   */
  public function testGetAllGroupBundles() {
    $groups = ['test_entity' => ['a', 'b']];

    $this->configProphecy->get('groups')
      ->willReturn($groups)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    $this->assertSame($groups, $manager->getAllGroupBundles());
  }

  /**
   * @covers ::isGroup
   *
   * @dataProvider providerTestIsGroup
   */
  public function testIsGroup($entity_type_id, $bundle_id, $expected) {
    $groups = ['test_entity' => ['a', 'b']];

    $this->configProphecy->get('groups')
      ->willReturn($groups)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    $this->assertSame($expected, $manager->isGroup($entity_type_id, $bundle_id));
  }

  /**
   * Data provider for testIsGroup
   *
   * @return array
   */
  public function providerTestIsGroup() {
    return [
      ['test_entity', 'a', TRUE],
      ['test_entity', 'b', TRUE],
      ['test_entity', 'c', FALSE],
      ['test_entity_non_existent', 'a', FALSE],
      ['test_entity_non_existent', 'c', FALSE],
    ];
  }

  /**
   * @covers ::getGroupsForEntityType
   */
  public function testGetGroupsForEntityType() {
    $groups = ['test_entity' => ['a', 'b']];

    $this->configProphecy->get('groups')
      ->willReturn($groups)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    $this->assertSame($groups['test_entity'], $manager->getGroupsForEntityType('test_entity'));
    $this->assertSame([], $manager->getGroupsForEntityType('test_entity_non_existent'));
  }

  /**
   * @covers ::addGroup
   */
  public function testAddGroupExisting() {
    $this->configFactoryProphecy->getEditable('og.settings')
      ->willReturn($this->configProphecy->reveal())
      ->shouldBeCalled();

    $groups_before = ['test_entity' => ['a', 'b']];

    $this->configProphecy->get('groups')
      ->willReturn($groups_before)
      ->shouldBeCalled();

    $groups_after = ['test_entity' => ['a', 'b', 'c']];

    $this->configProphecy->set('groups', $groups_after)
      ->shouldBeCalled();

    $this->configProphecy->save()
      ->shouldBeCalled();

    $this->configProphecy->get('groups')
      ->willReturn($groups_after)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    // Add to existing.
    $manager->addGroup('test_entity', 'c');
    $this->assertSame(['a', 'b', 'c'], $manager->getGroupsForEntityType('test_entity'));
    $this->assertTrue($manager->isGroup('test_entity', 'c'));
  }

  /**
   * @covers ::addGroup
   */
  public function testAddGroupNew() {
    $this->configFactoryProphecy->getEditable('og.settings')
      ->willReturn($this->configProphecy->reveal())
      ->shouldBeCalled();

    $groups_before = [];

    $this->configProphecy->get('groups')
      ->willReturn($groups_before)
      ->shouldBeCalled();

    $groups_after = ['test_entity_new' => ['a']];

    $this->configProphecy->set('groups', $groups_after)
      ->shouldBeCalled();

    $this->configProphecy->save()
      ->shouldBeCalled();

    $this->configProphecy->get('groups')
      ->willReturn($groups_after)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    // Add a new entity type.
    $manager->addGroup('test_entity_new', 'a');
    $this->assertSame(['a'], $manager->getGroupsForEntityType('test_entity_new'));
    $this->assertTrue($manager->isGroup('test_entity_new', 'a'));
  }

  /**
   * @covers ::addGroup
   */
  public function testRemoveGroup() {
    $this->configFactoryProphecy->getEditable('og.settings')
      ->willReturn($this->configProphecy->reveal())
      ->shouldBeCalled();

    $groups_before = ['test_entity' => ['a', 'b']];

    $this->configProphecy->get('groups')
      ->willReturn($groups_before)
      ->shouldBeCalled();

    $groups_after = ['test_entity' => ['a']];

    $this->configProphecy->set('groups', $groups_after)
      ->shouldBeCalled();

    $this->configProphecy->save()
      ->shouldBeCalled();

    $this->configProphecy->get('groups')
      ->willReturn($groups_after)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    // Add to existing.
    $manager->removeGroup('test_entity', 'b');
    $this->assertSame(['a'], $manager->getGroupsForEntityType('test_entity'));
    $this->assertFalse($manager->isGroup('test_entity', 'b'));
    $this->assertTrue($manager->isGroup('test_entity', 'a'));
  }

  /**
   * Creates a group manager instance with a mock config factory.
   *
   * @return \Drupal\og\GroupManager
   */
  protected function createGroupManager() {
    $this->configFactoryProphecy->get('og.settings')
      ->willReturn($this->configProphecy->reveal())
      ->shouldBeCalled();

    return new GroupManager($this->configFactoryProphecy->reveal());
  }

}
