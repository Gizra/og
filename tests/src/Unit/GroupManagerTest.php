<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\GroupManagerTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\og\Entity\OgRole;
use Drupal\og\GroupManager;
use Drupal\og\OgRoleInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManagerProphecy;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorageProphecy;

  /**
   * @var \Drupal\og\Entity\OgRole|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRoleProphecy;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeBundleInfoProphecy;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->configProphecy = $this->prophesize('Drupal\Core\Config\Config');
    $this->configFactoryProphecy = $this->prophesize('Drupal\Core\Config\ConfigFactoryInterface');
    $this->entityTypeManagerProphecy = $this->prophesize('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->entityStorageProphecy = $this->prophesize('Drupal\Core\Entity\EntityStorageInterface');
    $this->ogRoleProphecy = $this->prophesize('Drupal\og\Entity\OgRole');
    $this->entityTypeBundleInfoProphecy = $this->prophesize('Drupal\Core\Entity\EntityTypeBundleInfoInterface');
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
    $groups_before = ['test_entity' => ['a', 'b']];

    $this->configProphecy->get('groups')
      ->willReturn($groups_before)
      ->shouldBeCalled();

    $groups_after = ['test_entity' => ['a', 'b', 'c']];

    $this->configProphecy->get('groups')
      ->willReturn($groups_after)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    // Add to existing.
    try {
      $manager->addGroup('test_entity', 'c');
      $this->fail('An entity type that was already declared as a group, was redeclared as a group');
    } catch (\InvalidArgumentException $e) {
      // Expected result. An exception should be thrown when an entity type is
      // redeclared as a group.
    }
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

    $manager = $this->createGroupManager();

    $this->configProphecy->get('groups')
      ->willReturn($groups_after)
      ->shouldBeCalled();

    $this->expectDefaultRoleCreation('test_entity_new', 'a');

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

    $this->expectRoleRemoval('test_entity', 'b');

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
    $this->entityTypeManagerProphecy->getStorage('og_role')
      ->willReturn($this->entityStorageProphecy->reveal())
      ->shouldBeCalled();

    return new GroupManager($this->configFactoryProphecy->reveal(), $this->entityTypeManagerProphecy->reveal(), $this->entityTypeBundleInfoProphecy->reveal());
  }

  /**
   * Mocked method calls when system under test should create default roles.
   *
   * @param string $entity_type
   *   The entity type for which default roles should be created.
   * @param string $bundle
   *   The bundle for which default roles should be created.
   */
  protected function expectDefaultRoleCreation($entity_type, $bundle) {
    foreach ([OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED, OgRoleInterface::ADMINISTRATOR] as $role_name) {
      $this->addNewDefaultRole($entity_type, $bundle, $role_name);
    }
  }

  /**
   * Expected method calls when creating a new default role.
   *
   * @param string $entity_type
   *   The entity type for which the default role should be created.
   * @param string $bundle
   *   The bundle for which the default role should be created.
   * @param string $role_name
   *   The name of the role being created.
   */
  protected function addNewDefaultRole($entity_type, $bundle, $role_name) {
    // It is expected that the role will be created with default properties.
    $properties = [
      'group_type' => $entity_type,
      'group_bundle' => $bundle,
      'role_type' => OgRole::getRoleTypeByName($role_name),
      'id' => $role_name,
    ];
    $this->entityStorageProphecy->create($properties + OgRole::getProperties($role_name))
      ->willReturn($this->ogRoleProphecy->reveal())
      ->shouldBeCalled();

    // The role is expected to be saved.
    $this->ogRoleProphecy->save()
      ->willReturn(1)
      ->shouldBeCalled();
  }

  /**
   * Expected method calls when deleting roles after a group is deleted.
   *
   * @param string $entity_type_id
   *   The entity type for which the roles should be deleted.
   * @param string $bundle_id
   *   The bundle for which the roles should be deleted.
   */
  protected function expectRoleRemoval($entity_type_id, $bundle_id) {
    // It is expected that a call is done to retrieve all roles associated with
    // the group. This will return the 3 default role entities.
    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle_id,
    ];
    $this->entityStorageProphecy->loadByProperties($properties)
      ->willReturn([
        $this->ogRoleProphecy->reveal(),
        $this->ogRoleProphecy->reveal(),
        $this->ogRoleProphecy->reveal(),
      ])
      ->shouldBeCalled();

    // It is expected that all roles will be deleted, so three delete() calls
    // will be made.
    $this->ogRoleProphecy->delete()
      ->shouldBeCalledTimes(3);
  }

}
