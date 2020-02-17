<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\og\Event\GroupCreationEvent;
use Drupal\og\Event\GroupCreationEventInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\PermissionManagerInterface;
use Drupal\og\OgRoleManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\og\Entity\OgRole;
use Drupal\og\Event\PermissionEventInterface;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the group manager.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\GroupTypeManager
 */
class GroupTypeManagerTest extends UnitTestCase {

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
   * The cache prophecy used in the test.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cache;

  /**
   * The OG permission manager prophecy used in the test.
   *
   * @var \Drupal\og\PermissionManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $permissionManager;

  /**
   * The OG role manager prophecy used in the test.
   *
   * @var \Drupal\og\OgRoleManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRoleManager;

  /**
   * The route builder service used in the test.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeBuilder;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupAudienceHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->config = $this->prophesize(Config::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->entityTypeBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    $this->ogRole = $this->prophesize(OgRole::class);
    $this->ogRoleManager = $this->prophesize(OgRoleManagerInterface::class);
    $this->permissionEvent = $this->prophesize(PermissionEventInterface::class);
    $this->permissionManager = $this->prophesize(PermissionManagerInterface::class);
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->routeBuilder = $this->prophesize(RouteBuilderInterface::class);
    $this->groupAudienceHelper = $this->prophesize(OgGroupAudienceHelperInterface::class);
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
    $this->assertInstanceOf(GroupTypeManagerInterface::class, $group_manager);
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
   * Tests getting all the groups IDs of an entity type.
   *
   * @covers ::getGroupBundleIdsByEntityType
   */
  public function testGetGroupBundleIdsByEntityType() {
    // It is expected that the group map will be retrieved from config.
    $groups = ['test_entity' => ['a', 'b']];
    $this->expectGroupMapRetrieval($groups);

    $manager = $this->createGroupManager();

    $this->assertSame($groups['test_entity'], $manager->getGroupBundleIdsByEntityType('test_entity'));
    $this->assertSame([], $manager->getGroupBundleIdsByEntityType('test_entity_non_existent'));
  }

  /**
   * Tests adding an existing group.
   *
   * @covers ::addGroup
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
    $this->expectException(\InvalidArgumentException::class);
    $manager->addGroup('test_entity', 'c');

    $this->assertSame(['a', 'b', 'c'], $manager->getGroupBundleIdsByEntityType('test_entity'));
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

    $this->ogRoleManager->createPerBundleRoles('test_entity_new', 'a');

    $this->eventDispatcher->dispatch(GroupCreationEventInterface::EVENT_NAME, Argument::type(GroupCreationEvent::class))
      ->shouldBeCalled();

    // Add a new entity type.
    $manager->addGroup('test_entity_new', 'a');
    $this->assertSame(['a'], $manager->getGroupBundleIdsByEntityType('test_entity_new'));
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

    $manager = $this->createGroupManager();

    // Add to existing.
    $manager->removeGroup('test_entity', 'b');
    $this->assertSame(['a'], $manager->getGroupBundleIdsByEntityType('test_entity'));
    $this->assertFalse($manager->isGroup('test_entity', 'b'));
    $this->assertTrue($manager->isGroup('test_entity', 'a'));
  }

  /**
   * Creates a group manager instance with a mock config factory.
   *
   * @return \Drupal\og\GroupTypeManagerInterface
   *   Returns the group manager.
   */
  protected function createGroupManager() {
    return new GroupTypeManager(
      $this->configFactory->reveal(),
      $this->entityTypeManager->reveal(),
      $this->entityTypeBundleInfo->reveal(),
      $this->eventDispatcher->reveal(),
      $this->cache->reveal(),
      $this->permissionManager->reveal(),
      $this->ogRoleManager->reveal(),
      $this->routeBuilder->reveal(),
      $this->groupAudienceHelper->reveal()
    );
  }

  /**
   * Sets up an expectation that the group map will be retrieved from config.
   *
   * @param array $groups
   *   The expected group map that will be returned by the mocked config.
   */
  protected function expectGroupMapRetrieval(array $groups = []) {
    $this->configFactory->get('og.settings')
      ->willReturn($this->config->reveal())
      ->shouldBeCalled();

    $this->config->get('groups')
      ->willReturn($groups)
      ->shouldBeCalled();
  }

}
