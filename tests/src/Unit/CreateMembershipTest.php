<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManager;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests create membership helper function.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class CreateMembershipTest extends UnitTestCase {

  /**
   * The entity manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupAudienceHelper;

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
    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->groupAudienceHelper = $this->prophesize(OgGroupAudienceHelperInterface::class);

    $this->entityManager->getStorage('og_membership')
      ->willReturn($this->entityStorage->reveal());

    $this->entityManager->getEntityTypeFromClass('Drupal\og\Entity\OgMembership')
      ->willReturn('og_membership');

    // Create a mocked Og Membership entity.
    $membership_entity = $this->prophesize(OgMembershipInterface::class);

    $this->entityStorage
      ->create(Argument::type('array'))
      ->willReturn($membership_entity->reveal());

    // Create a mocked test group.
    $this->group = $this->prophesize(EntityInterface::class);

    // Create a mocked test user.
    $this->user = $this->prophesize(AccountInterface::class);

    $membership_entity
      ->setUser($this->user)
      ->willReturn($membership_entity->reveal());

    $membership_entity
      ->setGroup($this->group)
      ->willReturn($membership_entity->reveal());

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests creating membership for an un-saved group.
   *
   * @covers ::createMembership
   */
  public function testNewGroup() {
    $membership_manager = new MembershipManager($this->entityTypeManager->reveal(), $this->groupAudienceHelper->reveal());
    $membership = $membership_manager->createMembership($this->group->reveal(), $this->user->reveal());
    $this->assertInstanceOf(OgMembershipInterface::class, $membership);
  }

}
