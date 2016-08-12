<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\og\Event\DefaultRoleEvent;
use Drupal\og\Event\DefaultRoleEventInterface;
use Drupal\og\Event\GroupCreationEvent;
use Drupal\og\Event\GroupCreationEventInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\PermissionManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\Entity\OgRole;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\OgRoleInterface;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the group manager.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\GroupTypeManager
 */
class GroupManagerTest extends UnitTestCase {

  /**
   * The config prophecy used in the test.
   *
   * @var \Drupal\Core\Config\Config|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * The config factory prophecy used in the test.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The entity type manager prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The entity storage prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * The OG role prophecy used in the test.
   *
   * @var \Drupal\og\Entity\OgRole|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRole;

  /**
   * The entity type bundle info prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeBundleInfo;

  /**
   * The event dispatcher prophecy used in the test.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $eventDispatcher;

  /**
   * The permission event prophecy used in the test.
   *
   * @var \Drupal\og\Event\PermissionEventInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $permissionEvent;

  /**
   * The state prophecy used in the test.
   *
   * @var \Drupal\Core\State\StateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $state;

  /**
   * The OG permission manager prophecy used in the test.
   *
   * @var \Drupal\og\PermissionManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $permissionManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->config = $this->prophesize(Config::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->ogRole = $this->prophesize(OgRole::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    $this->permissionEvent = $this->prophesize(PermissionEventInterface::class);
    $this->state = $this->prophesize(StateInterface::class);
    $this->permissionManager = $this->prophesize(PermissionManagerInterface::class);
  }

  /**
   * Tests getting an instance of the group manager.
   *
   * @covers ::__construct
   */
  public function testInstance() {
    // Just creating an instance should be lightweight, no methods should be
    // called.
    $group_manager = $this->createGroupManager();
    $this->assertInstanceOf(GroupTypeManager::class, $group_manager);
  }

  /**
   * Tests getting all the group bundles.
   *
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
   * Tests checking if an entity is a group.
   *
   * @covers ::isGroup
   *
   * @dataProvider providerTestIsGroup
   */
  public function testIsGroup($entity_type_id, $bundle_id, $expected_result) {
    // It is expected that the group map will be retrieved from config.
    $groups = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups);

    $manager = $this->createGroupManager();

    $this->assertSame($expected_result, $manager->isGroup($entity_type_id, $bundle_id));
  }

  /**
   * Data provider for testIsGroup.
   *
   * @return array
   *   array with the entity type ID, bundle ID and boolean indicating the
   *   expected result.
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
   * Tests getting all the groups of an entity type.
   *
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
   * Tests adding an existing group.
   *
   * @covers ::addGroup
   * @expectedException \InvalidArgumentException
   */
  public function testAddGroupExisting() {
    // It is expected that the group map will be retrieved from config.
    $groups_before = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups_before);

    $groups_after = ['test_entity' => ['a', 'b', 'c']];

    $this->config->get('groups')
      ->willReturn($groups_after)
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    // Add to existing.
    $manager->addGroup('test_entity', 'c');

    $this->assertSame(['a', 'b', 'c'], $manager->getGroupsForEntityType('test_entity'));
    $this->assertTrue($manager->isGroup('test_entity', 'c'));
  }

  /**
   * Tests adding a new group.
   *
   * @covers ::addGroup
   */
  public function testAddGroupNew() {
    $this->configFactory->getEditable('og.settings')
      ->willReturn($this->config->reveal())
      ->shouldBeCalled();

    // It is expected that the group map will be retrieved from config.
    $groups_before = [];
    $this->expectGroupMapRetrieval($groups_before);

    $groups_after = ['test_entity_new' => ['a']];

    $config_prophecy = $this->config;
    $this->config->set('groups', $groups_after)
      ->will(function () use ($groups_after, $config_prophecy) {
        $config_prophecy->get('groups')
          ->willReturn($groups_after)
          ->shouldBeCalled();
      })
      ->shouldBeCalled();

    $this->config->save()
      ->shouldBeCalled();

    $manager = $this->createGroupManager();

    $this->expectDefaultRoleCreation('test_entity_new', 'a');

    $this->eventDispatcher->dispatch(GroupCreationEventInterface::EVENT_NAME, Argument::type(GroupCreationEvent::class))
      ->shouldBeCalled();

    // Add a new entity type.
    $manager->addGroup('test_entity_new', 'a');
    $this->assertSame(['a'], $manager->getGroupsForEntityType('test_entity_new'));
    $this->assertTrue($manager->isGroup('test_entity_new', 'a'));
  }

  /**
   * Tests removing a group.
   *
   * @covers ::addGroup
   */
  public function testRemoveGroup() {
    $this->configFactory->getEditable('og.settings')
      ->willReturn($this->config->reveal())
      ->shouldBeCalled();

    // It is expected that the group map will be retrieved from config.
    $groups_before = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups_before);

    $groups_after = ['test_entity' => ['a']];

    $this->config->set('groups', $groups_after)
      ->shouldBeCalled();

    $this->config->save()
      ->shouldBeCalled();

    $this->config->get('groups')
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
   * @return \Drupal\og\GroupTypeManager
   *   Returns the group manager.
   */
  protected function createGroupManager() {
    // It is expected that the role storage will be initialized.
    $this->entityTypeManager->getStorage('og_role')
      ->willReturn($this->entityStorage->reveal())
      ->shouldBeCalled();

    return new GroupTypeManager(
      $this->configFactory->reveal(),
      $this->entityTypeManager->reveal(),
      $this->entityTypeBundleInfo->reveal(),
      $this->eventDispatcher->reveal(),
      $this->state->reveal(),
      $this->permissionManager->reveal()
    );
  }

  /**
   * Sets up an expectation that the group map will be retrieved from config.
   *
   * @param array $groups
   *   The expected group map that will be returned by the mocked config.
   */
  protected function expectGroupMapRetrieval($groups = []) {
    $this->configFactory->get('og.settings')
      ->willReturn($this->config->reveal())
      ->shouldBeCalled();

    $this->config->get('groups')
      ->willReturn($groups)
      ->shouldBeCalled();
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
    // In order to populate the default roles for a new group type, it is
    // expected that the list of default roles to populate will be retrieved
    // from the event listener.
    $this->eventDispatcher->dispatch(DefaultRoleEventInterface::EVENT_NAME, Argument::type(DefaultRoleEvent::class))
      ->shouldBeCalled();

    foreach ([OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED] as $role_name) {
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
    // The Prophecy mocking framework uses 'promises' for dynamically generating
    // mocks that return context dependent data. This works by dynamically
    // setting the expected behaviors in an anonymous function. Make sure the
    // mocks are available in the local scope so they can be passed to the
    // anonymous functions.
    $permission_manager = $this->permissionManager;
    $og_role = $this->prophesize(OgRole::class);

    // It is expected that the role will be created with default properties.
    $this->entityStorage->create($this->getDefaultRoleProperties($role_name))
      ->will(function () use ($entity_type, $bundle, $role_name, $og_role, $permission_manager) {
        // It is expected that the OG permissions that need to be populated on
        // the new role will be requested. We are not testing permissions here
        // so we can just return an empty array.
        $permission_manager->getDefaultGroupPermissions($entity_type, $bundle, $role_name)
          ->willReturn([])
          ->shouldBeCalled();

        // For each role that is created it is expected that the role name will
        // be retrieved, so that the role name can be used to filter the
        // permissions.
        $og_role->getName()
          ->willReturn($role_name)
          ->shouldBeCalled();

        // The group type, bundle and permissions will have to be set on the new
        // role.
        $og_role->setGroupType($entity_type)->shouldBeCalled();
        $og_role->setGroupBundle($bundle)->shouldBeCalled();
        return $og_role->reveal();
      })
      ->shouldBeCalled();

    // The role is expected to be saved.
    $og_role->save()
      ->willReturn(1)
      ->shouldBeCalled();
  }

  /**
   * Returns the expected properties of the default role with the given name.
   *
   * @param string $role_name
   *   The name of the default role for which to return the properties.
   *
   * @return array
   *   The default properties.
   */
  protected function getDefaultRoleProperties($role_name) {
    $role_properties = [
      OgRoleInterface::ANONYMOUS => [
        'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
        'label' => 'Non-member',
        'name' => OgRoleInterface::ANONYMOUS,
      ],
      OgRoleInterface::AUTHENTICATED => [
        'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
        'label' => 'Member',
        'name' => OgRoleInterface::AUTHENTICATED,
      ],
    ];

    return $role_properties[$role_name];
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
    $this->entityTypeManager->getStorage('og_role')
      ->willReturn($this->entityStorage->reveal())
      ->shouldBeCalled();

    $properties = [
      'group_type' => $entity_type_id,
      'group_bundle' => $bundle_id,
    ];
    $this->entityStorage->loadByProperties($properties)
      ->willReturn([
        $this->ogRole->reveal(),
        $this->ogRole->reveal(),
        $this->ogRole->reveal(),
      ])
      ->shouldBeCalled();

    // It is expected that all roles will be deleted, so three delete() calls
    // will be made.
    $this->ogRole->delete()
      ->shouldBeCalledTimes(3);
  }

}
