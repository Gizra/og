<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\og\GroupManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;

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
   * @var \Drupal\Core\Field\FieldDefinitionInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fieldDefinitionInterface;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogAccess;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * The account proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $accountProxy;

  /**
   * A random group ID.
   *
   * @var int
   */
  protected $entityId;

  /**
   * A random user ID.
   *
   * @var int
   */
  protected $userId;

  /**
   * An access result object.
   *
   * @var \Drupal\Core\Access\AccessResult|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $accessResult;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->accessResult = $this->prophesize(AccessResultInterface::class);
    $this->accountProxy = $this->prophesize(AccountProxyInterface::class);
    $this->bundle = $this->randomMachineName();
    $this->entityId = rand(10, 50);
    $this->entityManager = $this->prophesize(EntityManagerInterface::class);
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeId = $this->randomMachineName();
    $this->fieldDefinitionInterface = $this->prophesize(FieldDefinitionInterface::class);
    $this->fieldItemList = $this->prophesize(FieldItemListInterface::class);
    $this->group = $this->prophesize(EntityInterface::class);
    $this->groupManager = $this->prophesize(GroupManager::class);
    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);
    $this->ogAccess = $this->prophesize(OgAccessInterface::class);
    $this->user = $this->prophesize(AccountInterface::class);
    $this->userId = rand(10, 50);

    $this
      ->fieldItemList
      ->getEntity()
      ->willReturn($this->group);

    $this
      ->group
      ->willImplement(EntityOwnerInterface::class);

    $this
      ->group
      ->getEntityTypeId()
      ->willReturn($this->entityTypeId);

    $this
      ->group
      ->bundle()
      ->willReturn($this->bundle);

    $this
      ->group
      ->id()
      ->willReturn($this->entityId);

    $this->groupManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(TRUE);
    $this->entityManager->getStorage('user')
      ->willReturn($this->entityStorage->reveal());

    $this
      ->accountProxy
      ->id()
      ->willReturn($this->userId);

    $this->entityManager->getEntityTypeFromClass('Drupal\user\Entity\User')
      ->willReturn('user');

    $this->entityStorage
      ->load($this->userId)
      ->willReturn($this->user->reveal());

    $this
      ->user
      ->isAuthenticated()
      ->willReturn(TRUE);

    $this
      ->user
      ->id()
      ->willReturn($this->userId);

    $container = new ContainerBuilder();
    $container->set('current_user', $this->accountProxy->reveal());
    $container->set('entity.manager', $this->entityManager->reveal());
    $container->set('og.access', $this->ogAccess->reveal());
    $container->set('og.group.manager', $this->groupManager->reveal());
    $container->set('og.membership_manager', $this->membershipManager->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());

    \Drupal::setContainer($container);
  }

  /**
   * Tests the formatter for a group owner.
   */
  public function testGroupOwner() {
    // Return the same ID as the user.
    $this->group->getOwnerId()->willReturn($this->userId);

    $formatter = new GroupSubscribeFormatter('', [], $this->fieldDefinitionInterface->reveal(), [], '', [], []);
    $elements = $formatter->viewElements($this->fieldItemList->reveal(), $this->randomMachineName());

    $this->assertEquals('You are the group manager', $elements[0]['#value']);
  }

  /**
   * Tests the formatter for an "active" group member.
   */
  public function testGroupMemberActive() {
    $this->group->getOwnerId()->willReturn(rand(100, 200));

    $this
      ->ogAccess
      ->userAccess($this->group->reveal(), 'subscribe without approval', $this->user->reveal())
      ->willReturn($this->accessResult->reveal());

    $this
      ->accessResult
      ->isAllowed()
      ->willReturn(TRUE);

    $formatter = new GroupSubscribeFormatter('', [], $this->fieldDefinitionInterface->reveal(), [], '', [], []);
    $elements = $formatter->viewElements($this->fieldItemList->reveal(), $this->randomMachineName());

    $this->assertEquals('Subscribe to group', $elements[0]['#title']);
  }

}
