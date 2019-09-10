<?php

namespace Drupal\Tests\og\Unit\Cache\Context;

use Drupal\Core\Session\AccountInterface;
use Drupal\og\Cache\Context\OgMembershipStateCacheContext;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;

/**
 * Tests OG membership state cache context.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Cache\Context\OgMembershipStateCacheContext
 */
class OgMembershipStateCacheContextTest extends OgContextCacheContextTestBase {

  /**
   * The OG membership entity.
   *
   * @var \Drupal\og\OgMembershipInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membership;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->prophesize(AccountInterface::class);
    $this->membership = $this->prophesize(OgMembershipInterface::class);
    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function testWithoutContext() {
    $this->expectGroupContext();

    $result = $this->getContextResult();
    $this->assertEquals(OgMembershipStateCacheContext::NO_CONTEXT, $result);
  }

  /**
   * {@inheritdoc}
   */
  protected function setupExpectedContext($context) {
    $this->expectGroupContext($this->group->reveal());
    $this->expectMembership($context);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheContext() {
    return new OgMembershipStateCacheContext($this->user->reveal(), $this->ogContext->reveal(), $this->membershipManager->reveal());
  }

  /**
   * Sets an expectation that the current user has the given membership state.
   *
   * @param string|false $state
   *   The membership state, or FALSE if the user is not a member.
   */
  protected function expectMembership($state) {
    // If the user is a member, it is expected that the membership state will be
    // retrieved.
    if ($state) {
      $this->membership->getState()
        ->willReturn($state);
      $state = $this->membership;
    }

    // It is expected that the user membership will be retrieved, of any
    // possible membership state.
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    $this->membershipManager
      ->getMembership($this->group->reveal(), $this->user->reveal()->id(), $states)
      ->willReturn($state);
  }

  /**
   * {@inheritdoc}
   */
  public function contextProvider() {
    return [
      [
        FALSE,
        OgMembershipStateCacheContext::NO_CONTEXT,
      ],
      [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_ACTIVE,
      ],
      [
        OgMembershipInterface::STATE_PENDING,
        OgMembershipInterface::STATE_PENDING,
      ],
      [
        OgMembershipInterface::STATE_BLOCKED,
        OgMembershipInterface::STATE_BLOCKED,
      ],
    ];
  }

}
