<?php

namespace Drupal\Tests\og\Unit;


use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
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
    $this->entityManager= $this->prophesize(EntityManagerInterface::class);

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
    $group_entity = $this->prophesize(EntityInterface::class);
    $group_entity->getEntityTypeId()->willReturn($this->entityTypeId);
    $group_entity->bundle()->willReturn($this->bundle);
    $group_entity->id()->willReturn($this->randomMachineName());

    $this->group = $group_entity;

    // Create a mocked test user.
    $this->user = $this->prophesize(AccountInterface::class);
    $this->user->id()->willReturn(rand(10, 50));

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
    $membership = Og::createMembership($this->group->reveal(), $this->user->reveal());

    $this->assertInstanceOf(OgMembershipInterface::class, $membership);
  }


}