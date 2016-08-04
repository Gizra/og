<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\og\GroupManager;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;
use Prophecy\Argument;

/**
 * Tests the OG group formatter.
 *
 * @group og
 * @coversDefaultClass Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter
 */
class GroupSubscribeFormatterTest extends UnitTestCase {

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
   * @var \Drupal\og\OgMembershipInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membership;

  /**
   * The field item lists.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fieldItemList;

  /**
   * The group manager.
   *
   * @var \Drupal\og\GroupManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupManager;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinitionInterface;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->accountProxy = $this->prophesize(AccountProxyInterface::class);
    $this->entityId = rand(10, 50);
    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();
    $this->fieldDefinitionInterface = $this->prophesize(FieldDefinitionInterface::class);
    $this->fieldItemList = $this->prophesize(FieldItemListInterface::class);
    $this->group = $this->prophesize(EntityInterface::class);
    $this->groupManager = $this->prophesize(GroupManager::class);
    $this->user = $this->prophesize(AccountInterface::class);
    $this->userId = rand(10, 50);

    $this
      ->fieldItemList
      ->getEntity()
      ->willReturn($this->group);

    $this
      ->group
      ->getEntityTypeId()
      ->willReturn($this->entityTypeId);

    $this
      ->group
      ->bundle()
      ->willReturn($this->bundle);


    $container = new ContainerBuilder();
    $container->set('og.group.manager', $this->groupManager->reveal());
    $container->set('current_user', $this->accountProxy->reveal());
    \Drupal::setContainer($container);

//    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
//    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
//
//    $this->entityManager->getStorage('og_membership')
//      ->willReturn($this->entityStorage->reveal());
//
//    $this->entityManager->getEntityTypeFromClass('Drupal\og\Entity\OgMembership')
//      ->willReturn('og_membership');
//
//    // Create a mocked Og Membership entity.
//    $membership_entity = $this->prophesize(OgMembershipInterface::class);
//
//    $this->entityStorage
//      ->create(Argument::type('array'))
//      ->willReturn($membership_entity->reveal());
//
//    // Create a mocked test group.
//    $this->group = $this->prophesize(EntityInterface::class);
//
//    // Create a mocked test user.
//    $this->user = $this->prophesize(AccountInterface::class);
//
//    $membership_entity
//      ->setUser($this->user)
//      ->willReturn($membership_entity->reveal());
//
//    $membership_entity
//      ->setGroup($this->group)
//      ->willReturn($membership_entity->reveal());
//
//    $container = new ContainerBuilder();
//    $container->set('entity.manager', $this->entityManager->reveal());
//    \Drupal::setContainer($container);
  }

  /**
   * Tests formatter on a non-group.
   *
   * This verifies an edge case, where the formatter was somehow added to a
   * non-group entity.
   *
   * @covers ::viewElements
   */
  public function testNonGroup() {
    $this->groupManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(FALSE);

    // $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings)
    $formatter = new GroupSubscribeFormatter('', [], $this->fieldDefinitionInterface->reveal(), [], '', [], []);
    $elements = $formatter->viewElements($this->fieldItemList->reveal(), $this->randomMachineName());
    $this->assertArrayEquals([], $elements);
  }

  public function testGroupOwner() {
    $this->groupManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(TRUE);
    $this->entityManager->getStorage('user')
      ->willReturn($this->entityStorage->reveal());

    $this
      ->accountProxy
      ->id()
      ->willReturn($this->entityId);

    $this->entityManager->getEntityTypeFromClass('Drupal\user\Entity\User')
      ->willReturn('user');

    $this->entityStorage
      ->load($this->entityId)
      ->willReturn($this->user->reveal());

    $this
      ->user
      ->id()
      ->willReturn($this->userId);

    $this
      ->group
      ->getOwnerId()
      ->willReturn($this->userId);

    $formatter = new GroupSubscribeFormatter('', [], $this->fieldDefinitionInterface->reveal(), [], '', [], []);
    $elements = $formatter->viewElements($this->fieldItemList->reveal(), $this->randomMachineName());
    $this->assertArrayEquals([], $elements);
  }

}
