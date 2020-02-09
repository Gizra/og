<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;

/**
 * Tests the OG group formatter.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter
 */
class GroupSubscribeFormatterTest extends UnitTestCase {

  /**
   * The entity storage prophecy used in the test.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The mocked entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeRepository;

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
   * @var \Drupal\og\GroupTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;

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
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeId = $this->randomMachineName();
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeRepository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $this->fieldDefinitionInterface = $this->prophesize(FieldDefinitionInterface::class);
    $this->fieldItemList = $this->prophesize(FieldItemListInterface::class);
    $this->group = $this->prophesize(EntityInterface::class);
    $this->groupTypeManager = $this->prophesize(GroupTypeManagerInterface::class);
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

    $this->groupTypeManager->isGroup($this->entityTypeId, $this->bundle)->willReturn(TRUE);
    $this->entityTypeManager->getStorage('user')
      ->willReturn($this->entityStorage->reveal());

    $this
      ->accountProxy
      ->id()
      ->willReturn($this->userId);

    $this->entityTypeRepository->getEntityTypeFromClass('Drupal\user\Entity\User')
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
    $container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $container->set('entity_type.repository', $this->entityTypeRepository->reveal());
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

    $elements = $this->getElements();
    $this->assertEquals('You are the group manager', $elements[0]['#value']);
  }

  /**
   * Tests the formatter for an "active" group member.
   */
  public function testGroupMemberActive() {
    $this->group->getOwnerId()->willReturn(rand(100, 200));

    $this->ogAccess->userAccess($this->group->reveal(), 'subscribe without approval', $this->user->reveal())->willReturn($this->accessResult->reveal());
    $this->accessResult->isAllowed()->willReturn(TRUE);

    $elements = $this->getElements();
    $this->assertEquals('Subscribe to group', $elements[0]['#title']);
  }

  /**
   * Tests the formatter for subscribe without approval.
   */
  public function testSubscribeWithoutApprovalPermission() {
    $this->group->getOwnerId()->willReturn(rand(100, 200));

    $this->ogAccess->userAccess($this->group->reveal(), 'subscribe without approval', $this->user->reveal())->willReturn($this->accessResult->reveal());
    $this->accessResult->isAllowed()->willReturn(TRUE);

    $elements = $this->getElements();
    $this->assertEquals('Subscribe to group', $elements[0]['#title']);
  }

  /**
   * Tests the formatter for subscribe with approval.
   */
  public function testSubscribeWithApprovalPermission() {
    $this->group->getOwnerId()->willReturn(rand(100, 200));

    $this
      ->ogAccess
      ->userAccess($this->group->reveal(), 'subscribe without approval', $this->user->reveal())
      ->willReturn($this->accessResult->reveal());

    $this->accessResult->isAllowed()->willReturn(FALSE);

    /** @var \Drupal\Core\Access\AccessResult $access_result */
    $access_result = $this->prophesize(AccessResultInterface::class);
    $this
      ->ogAccess
      ->userAccess($this->group->reveal(), 'subscribe', $this->user->reveal())
      ->willReturn($access_result->reveal());

    $access_result->isAllowed()->willReturn(TRUE);

    $elements = $this->getElements();
    $this->assertEquals('Request group membership', $elements[0]['#title']);
  }

  /**
   * Tests the formatter for no subscribe permission.
   */
  public function testNoSubscribePermission() {
    $this->group->getOwnerId()->willReturn(rand(100, 200));

    foreach (['subscribe without approval', 'subscribe'] as $perm) {
      $this
        ->ogAccess
        ->userAccess($this->group->reveal(), $perm, $this->user->reveal())
        ->willReturn($this->accessResult->reveal());
    }

    $this->accessResult->isAllowed()->willReturn(FALSE);

    $elements = $this->getElements();
    $this->assertTrue(strpos($elements[0]['#value'], 'This is a closed group.') === 0);
  }

  /**
   * Tests the formatter for a blocked member.
   */
  public function testBlockedMember() {
    $this->group->getOwnerId()->willReturn(rand(100, 200));

    $this
      ->membershipManager
      ->isMember($this->group->reveal(), $this->user->reveal()->id(), [OgMembershipInterface::STATE_BLOCKED])
      ->willReturn(TRUE);

    $elements = $this->getElements();
    $this->assertTrue(empty($elements[0]));
  }

  /**
   * Tests the formatter for an active or pending member.
   */
  public function testMember() {
    $this->group->getOwnerId()->willReturn(rand(100, 200));

    $this
      ->membershipManager
      ->isMember($this->group->reveal(), $this->user->reveal()->id(), [OgMembershipInterface::STATE_BLOCKED])
      ->willReturn(FALSE);

    $this
      ->membershipManager
      ->isMember($this->group->reveal(), $this->user->reveal()->id(), [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING])
      ->willReturn(TRUE);

    $elements = $this->getElements();
    $this->assertEquals('Unsubscribe from group', $elements[0]['#title']);
  }

  /**
   * Helper method; Return the renderable elements from the formatter.
   *
   * @return array
   *   The renderable array.
   */
  protected function getElements() {
    $formatter = new GroupSubscribeFormatter(
      '',
      [],
      $this->fieldDefinitionInterface->reveal(),
      [],
      '',
      '',
      [],
      $this->accountProxy->reveal(),
      $this->ogAccess->reveal(),
      $this->entityTypeManager->reveal()
    );
    return $formatter->viewElements($this->fieldItemList->reveal(), $this->randomMachineName());
  }

}
