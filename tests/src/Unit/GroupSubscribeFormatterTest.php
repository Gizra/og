<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupManager;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter;
use Drupal\Tests\UnitTestCase;
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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->group = $this->prophesize(EntityInterface::class);

    $this->fieldItemList = $this->prophesize(FieldItemListInterface::class);
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

    $this->groupManager = $this->prophesize(GroupManager::class);
    $this
      ->groupManager
      ->isGroup($this->entityTypeId, $this->bundle)
      ->willReturn(FALSE);

    $this->fieldDefinitionInterface = $this->prophesize(FieldDefinitionInterface::class);

    $container = new ContainerBuilder();
    $container->set('og.group.manager', $this->groupManager->reveal());
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
    // $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings)
    $formatter = new GroupSubscribeFormatter('', [], $this->fieldDefinitionInterface->reveal(), [], '', [], []);
    $elements = $formatter->viewElements($this->fieldItemList->reveal(), $this->randomMachineName());
    $this->assertArrayEquals([], $elements);
  }

}
