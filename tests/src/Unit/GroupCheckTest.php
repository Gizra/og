<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Access\GroupCheck;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgAccessInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests the group check access.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Access\GroupCheck
 */
class GroupCheckTest extends UnitTestCase {

  /**
   * The entity type manager prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The entity type prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityType;

  /**
   * The entity storage prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * The OG access service prophecy used in the test.
   *
   * @var \Drupal\og\OgAccess|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogAccess;

  /**
   * The route service prophecy used in the test.
   *
   * @var \Symfony\Component\Routing\Route|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $route;

  /**
   * A user used in the test.
   *
   * @var \Drupal\user\UserInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * The entity type ID of the test group.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID of the test group.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The test group entity used in the test..
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * A random entity ID.
   *
   * @var int
   */
  protected $entityId;

  /**
   * The group manager used in the test.
   *
   * @var \Drupal\og\GroupTypeManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;

  /**
   * The access result used in the test.
   *
   * @var \Drupal\Core\Access\AccessResultInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $accessResult;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->ogAccess = $this->prophesize(OgAccessInterface::class);
    $this->route = $this->prophesize(Route::class);

    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();
    $this->entityId = rand(10, 50);
    $this->groupTypeManager = $this->prophesize(GroupTypeManager::class);
    $this->user = $this->prophesize(AccountInterface::class);
    $this->group = $this->prophesize(EntityInterface::class);
    $this->accessResult = $this->prophesize(AccessResultInterface::class);

    $container = new ContainerBuilder();
    $container->set('group_type_manager', $this->groupTypeManager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests an invalid entity type.
   *
   * @covers ::access
   */
  public function testInvalidEntityType() {
    $this
      ->entityTypeManager
      ->getDefinition($this->entityTypeId, FALSE)
      ->willReturn(NULL);

    $group_check = new GroupCheck($this->entityTypeManager->reveal(), $this->ogAccess->reveal());
    $result = $group_check->access($this->user->reveal(), $this->route->reveal(), $this->entityTypeId, $this->entityId);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests a non-existing group.
   *
   * @covers ::access
   */
  public function testNoGroup() {
    $this
      ->entityTypeManager
      ->getDefinition($this->entityTypeId, FALSE)
      ->willReturn($this->entityType);

    $this
      ->entityTypeManager
      ->getStorage($this->entityTypeId)
      ->willReturn($this->entityStorage);

    $this->entityStorage
      ->load($this->entityId)
      ->willReturn(NULL);

    $group_check = new GroupCheck($this->entityTypeManager->reveal(), $this->ogAccess->reveal());
    $result = $group_check->access($this->user->reveal(), $this->route->reveal(), $this->entityTypeId, $this->entityId);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests an entity that is not of group type.
   *
   * @covers ::access
   */
  public function testNotGroupType() {
    $this
      ->entityTypeManager
      ->getDefinition($this->entityTypeId, FALSE)
      ->willReturn($this->entityType);

    $this
      ->entityTypeManager
      ->getStorage($this->entityTypeId)
      ->willReturn($this->entityStorage);

    $this->entityStorage
      ->load($this->entityId)
      ->willReturn($this->group->reveal());

    $this
      ->group
      ->bundle()
      ->willReturn($this->bundle);

    $this->groupTypeManager
      ->isGroup($this->entityTypeId, $this->bundle)
      ->willReturn(FALSE);

    $group_check = new GroupCheck($this->entityTypeManager->reveal(), $this->ogAccess->reveal());
    $result = $group_check->access($this->user->reveal(), $this->route->reveal(), $this->entityTypeId, $this->entityId);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Tests an in-accessible and accessible routes.
   *
   * @covers ::access
   * @dataProvider permissionsProvider
   */
  public function testPermissions($permissions, $expected) {
    $this
      ->entityTypeManager
      ->getDefinition($this->entityTypeId, FALSE)
      ->willReturn($this->entityType);

    $this
      ->entityTypeManager
      ->getStorage($this->entityTypeId)
      ->willReturn($this->entityStorage);

    $this->entityStorage
      ->load($this->entityId)
      ->willReturn($this->group);

    $this
      ->group
      ->bundle()
      ->willReturn($this->bundle);

    $this->groupTypeManager
      ->isGroup($this->entityTypeId, $this->bundle)
      ->willReturn(TRUE);

    $this
      ->route
      ->getRequirement('_og_user_access_group')
      ->willReturn($permissions);

    foreach (explode('|', $permissions) as $permission) {
      // Check explicitly that only the permissions we passed were used.
      $this
        ->ogAccess
        ->userAccess($this->group->reveal(), $permission, $this->user->reveal())
        ->willReturn($this->accessResult);
    }

    $this
      ->accessResult
      ->isAllowed()
      ->willReturn($expected);

    $group_check = new GroupCheck($this->entityTypeManager->reveal(), $this->ogAccess->reveal());
    $result = $group_check->access($this->user->reveal(), $this->route->reveal(), $this->entityTypeId, $this->entityId);

    $actual = $expected ? $result->isAllowed() : $result->isForbidden();
    $this->assertTrue($actual);
  }

  /**
   * Provides test data to test permissions.
   *
   * @return array
   *   Array with the permission names, and the expected access result as
   *   boolean.
   */
  public function permissionsProvider() {
    return [
      ['foo', FALSE],
      ['foo', TRUE],
      ['foo|bar', FALSE],
      ['foo|bar', TRUE],
    ];
  }

}
