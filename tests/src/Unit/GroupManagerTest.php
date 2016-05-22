<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\GroupManagerTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\GroupManager;

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
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeBundleInfoProphecy;

  /**
   * @var \Drupal\Core\State\StateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $stateProphecy;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->configProphecy = $this->prophesize(Config::class);
    $this->configFactoryProphecy = $this->prophesize(ConfigFactoryInterface::class);
    $this->entityTypeBundleInfoProphecy = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->stateProphecy = $this->prophesize(StateInterface::class);
  }

  /**
   * @covers ::__construct
   */
  public function testInstance() {
    // Just creating an instance should be lightweight, no methods should be
    // called.
    $group_manager = $this->createGroupManager();
    $this->assertInstanceOf(GroupManager::class, $group_manager);
  }

  /**
   * @covers ::getAllGroupBundles
   */
  public function testGetAllGroupBundles() {
    // It is expected that the group map will be retrieved from config.
    $groups = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups);

    $manager = $this->createGroupManager();

    $this->assertSame($groups, $manager->getAllGroupBundles());
  }

  /**
   * @covers ::isGroup
   *
   * @dataProvider providerTestIsGroup
   */
  public function testIsGroup($entity_type_id, $bundle_id, $expected) {
    // It is expected that the group map will be retrieved from config.
    $groups = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups);

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
    // It is expected that the group map will be retrieved from config.
    $groups = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups);

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

    // It is expected that the group map will be retrieved from config.
    $groups_before = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups_before);

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

    // It is expected that the group map will be retrieved from config.
    $groups_before = [];
    $this->expectGroupMapRetrieval($groups_before);

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

    // It is expected that the group map will be retrieved from config.
    $groups_before = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups_before);

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
    return new GroupManager(
      $this->configFactoryProphecy->reveal(),
      $this->entityTypeBundleInfoProphecy->reveal(),
      $this->stateProphecy->reveal()
    );
  }

  /**
   * Sets up an expectation that the group map will be retrieved from config.
   *
   * @param array $groups
   *   The expected group map that will be returned by the mocked config.
   */
  protected function expectGroupMapRetrieval($groups = []) {
    $this->configFactoryProphecy->get('og.settings')
      ->willReturn($this->configProphecy->reveal())
      ->shouldBeCalled();

    $this->configProphecy->get('groups')
      ->willReturn($groups)
      ->shouldBeCalled();
  }

}
