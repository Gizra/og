<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\og\MembershipManager;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Prophecy\Argument;

/**
 * Tests create membership helper function.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class CreateMembershipTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The mocked entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeRepository;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupAudienceHelper;

  /**
   * The mocked memory cache backend.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $staticCache;

  /**
   * The entity storage prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * A mocked test user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy
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
   * The mocked test OG membership.
   *
   * @var \Drupal\og\OgMembershipInterface
   */
  protected $membership;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeRepository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $this->groupAudienceHelper = $this->prophesize(OgGroupAudienceHelperInterface::class);
    $this->staticCache = $this->prophesize(MemoryCacheInterface::class);

    $this->entityTypeManager->getStorage('og_membership')
      ->willReturn($this->entityStorage->reveal());

    $this->entityTypeRepository->getEntityTypeFromClass('Drupal\og\Entity\OgMembership')
      ->willReturn('og_membership');

    // Create a mocked Og Membership entity.
    /** @var \Drupal\og\OgMembershipInterface|\Prophecy\Prophecy\ObjectProphecy $membership_entity */
    $membership_entity = $this->prophesize(OgMembershipInterface::class);

    $this->entityStorage
      ->create(Argument::type('array'))
      ->willReturn($membership_entity->reveal());

    // Create a mocked test group.
    $this->group = $this->prophesize(ContentEntityInterface::class);

    // Create a mocked test user.
    $this->user = $this->prophesize(UserInterface::class);

    $membership_entity
      ->setOwner($this->user)
      ->willReturn($membership_entity->reveal());

    $membership_entity
      ->setGroup($this->group)
      ->willReturn($membership_entity->reveal());

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity_type.repository', $this->entityTypeRepository->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests creating membership for an un-saved group.
   *
   * @covers ::createMembership
   */
  public function testNewGroup() {
    $membership_manager = new MembershipManager($this->entityTypeManager->reveal(), $this->groupAudienceHelper->reveal(), $this->staticCache->reveal());
    $membership = $membership_manager->createMembership($this->group->reveal(), $this->user->reveal());
    $this->assertInstanceOf(OgMembershipInterface::class, $membership);
  }

}
