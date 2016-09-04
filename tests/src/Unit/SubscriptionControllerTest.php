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
    $this->url = $this->prophesize(Url::class);
    $this->user = $this->prophesize(AccountInterface::class);

    // Set the container for the string translation service.
    $container = new ContainerBuilder();
    $container->set('current_user', $this->user->reveal());
    $container->set('og.membership_manager', $this->membershipManager->reveal());
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
      ->willReturn(FALSE);

    $this->getUnsubscribeResult();
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
      ->willReturn(TRUE);

    $this
      ->membershipManager
      ->isMember($this->group->reveal(), $this->user->reveal(), [OgMembershipInterface::STATE_BLOCKED])
      ->willReturn(TRUE);

    $this->getUnsubscribeResult();
  }

  /**
   * Get the result of the unsubscribe method.
   *
   * @return mixed
   *   Access defnied, redirect or renderable array.
   */
  protected function getUnsubscribeResult() {
    $controller = new SubscriptionController($this->ogAccess->reveal());
    return $controller->unsubscribe($this->group->reveal());
  }

}
