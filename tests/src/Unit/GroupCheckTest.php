<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Access\GroupCheck;
use Drupal\og\GroupManager;
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
   * A mocked test user.
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
   * The mocked test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * The mocked entity ID.
   *
   * @var int
   */
  protected $entityId;

  /**
   * The mocked group manager.
   *
   * @var \Drupal\og\GroupManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupManager;

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
    $this->groupManager = $this->prophesize(GroupManager::class);
    $this->user = $this->prophesize(AccountInterface::class);
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

}
