<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\og\Controller\SubscriptionController;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;

/**
 * Tests the subscription controller.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Controller\SubscriptionController
 */
class SubscriptionControllerTest extends UnitTestCase {

  /**
   * The entity for builder object.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityFormBuilder;

  /**
   * The group entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * OG access service.
   *
   * @var \Drupal\og\OgAccessInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogAccess;

  /**
   * The OG membership entity.
   *
   * @var \Drupal\og\OgMembershipInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogMembership;

  /**
   * The URL object.
   *
   * @var \Drupal\Core\Url|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $url;

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityFormBuilder = $this->prophesize(EntityFormBuilderInterface::class);
    $this->group = $this->prophesize(ContentEntityInterface::class);
    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);
    $this->ogAccess = $this->prophesize(OgAccessInterface::class);
    $this->ogMembership = $this->prophesize(OgMembershipInterface::class);
    $this->url = $this->prophesize(Url::class);
    $this->user = $this->prophesize(AccountInterface::class);

    // Set the container for the string translation service.
    $container = new ContainerBuilder();
    $container->set('current_user', $this->user->reveal());
    $container->set('entity.form_builder', $this->entityFormBuilder->reveal());
    $container->set('og.membership_manager', $this->membershipManager->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

  }

  /**
   * Tests non-member trying to unsubscribe from group.
   *
   * @covers ::unsubscribe
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function testNotMember() {
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    $this
      ->membershipManager
      ->getMembership($this->group->reveal(), $this->user->reveal(), $states)
      ->willReturn(NULL);

    $this->unsubscribe();
  }

  /**
   * Tests blocked member trying to unsubscribe from group.
   *
   * @covers ::unsubscribe
   * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function testBlockedMember() {
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    $this
      ->membershipManager
      ->getMembership($this->group->reveal(), $this->user->reveal(), $states)
      ->willReturn($this->ogMembership->reveal());

    $this
      ->ogMembership
      ->getState()
      ->willReturn(OgMembershipInterface::STATE_BLOCKED);

    $this->unsubscribe();
  }

  /**
   * Tests active and pending members trying to unsubscribe from group.
   *
   * @covers ::unsubscribe
   * @dataProvider memberProvider
   */
  public function testMember($state) {
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    $this
      ->membershipManager
      ->getMembership($this->group->reveal(), $this->user->reveal(), $states)
      ->willReturn($this->ogMembership->reveal());

    $this
      ->ogMembership
      ->getState()
      ->willReturn($state);

    $this
      ->entityFormBuilder
      ->getForm($this->ogMembership->reveal(), 'unsubscribe')
      ->shouldBeCalled();

    $this->unsubscribe();
  }

  /**
   * Provides test data to test members unsubscribe.
   *
   * @return array
   *   Array with the membership state.
   */
  public function memberProvider() {
    return [
      [OgMembershipInterface::STATE_ACTIVE],
      [OgMembershipInterface::STATE_PENDING],
    ];
  }

  /**
   * Tests group manager trying to unsubscribe from group.
   *
   * @covers ::unsubscribe
   * @dataProvider memberProvider
   */
  public function testGroupManager($state) {
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    $this
      ->group
      ->willImplement(EntityOwnerInterface::class);

    $this
      ->membershipManager
      ->getMembership($this->group->reveal(), $this->user->reveal(), $states)
      ->willReturn($this->ogMembership->reveal());

    $this
      ->ogMembership
      ->getState()
      ->willReturn($state);

    $entity_id = rand(20, 50);

    $this
      ->user
      ->id()
      ->willReturn($entity_id);

    $this
      ->group
      ->getOwnerId()
      ->willReturn($entity_id);

    $this
      ->group
      ->label()
      ->shouldBeCalled();

    $this
      ->group
      ->toUrl()
      ->willReturn($this->url->reveal());

    $this
      ->url
      ->setAbsolute()
      ->willReturn($this->url->reveal());

    $this
      ->url
      ->toString()
      ->willReturn($this->randomMachineName());

    $this
      ->entityFormBuilder
      ->getForm($this->ogMembership->reveal(), 'unsubscribe')
      ->shouldNotBeCalled();

    $this->unsubscribe();
  }

  /**
   * Invoke the unsubscribe method.
   */
  protected function unsubscribe() {
    $controller = new SubscriptionController($this->ogAccess->reveal());
    $controller->unsubscribe($this->group->reveal());
  }

}

// @todo Delete after https://www.drupal.org/node/1858196 is in.
namespace Drupal\og\Controller;

if (!function_exists('drupal_set_message')) {

  /**
   * Mocking for drupal_set_message().
   */
  function drupal_set_message() {
  }

}
