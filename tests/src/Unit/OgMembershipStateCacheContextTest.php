<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Cache\Context\OgMembershipStateCacheContext;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgContextInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests OG membership state cache context.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Cache\Context\OgMembershipStateCacheContext
 */
class OgMembershipStateCacheContextTest extends UnitTestCase {

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
   * The mocked OG context service.
   *
   * @var \Drupal\og\OgContextInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogContext;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * The group entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->user = $this->prophesize(AccountInterface::class);
    $this->ogContext = $this->prophesize(OgContextInterface::class);

    $this->group = $this->prophesize(EntityInterface::class);

    $this->membership = $this->prophesize(OgMembershipInterface::class);
    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);
  }

  /**
   * Tests getting cache context when there is no matching group on the route.
   *
   * @covers ::getContext
   */
  public function testNoGroupOnRoute() {
    $this
      ->ogContext
      ->getGroup()
      ->willReturn(NULL);

    $result = $this->getContextResult();
    $this->assertEquals(OgMembershipStateCacheContext::NO_CONTEXT, $result);
  }

  /**
   * Tests user with no membership.
   *
   * @covers ::getContext
   */
  public function testNoMembership() {
    $this->expectGroupContext();
    $this->expectMembership(FALSE);

    $result = $this->getContextResult();
    $this->assertEquals(OgMembershipStateCacheContext::NO_CONTEXT, $result);
  }

  /**
   * Tests user that is a member with different states.
   *
   * @covers ::getContext
   * @dataProvider membershipProvider
   */
  public function testMembership($state) {
    $this->expectGroupContext();
    $this->expectMembership($state);

    $result = $this->getContextResult();
    $this->assertEquals($state, $result);
  }

  /**
   * Provides test data for the membership test.
   *
   * @return array
   *   An array of test data arrays, each with the OG membership state.
   */
  public function membershipProvider() {
    return [
      [OgMembershipInterface::STATE_ACTIVE],
      [OgMembershipInterface::STATE_PENDING],
      [OgMembershipInterface::STATE_BLOCKED],
    ];
  }

  /**
   * Return the context result.
   *
   * @return string
   *   The context result.
   */
  protected function getContextResult() {
    $cache_context = new OgMembershipStateCacheContext($this->user->reveal(), $this->ogContext->reveal(), $this->membershipManager->reveal());
    return $cache_context->getContext();
  }

  /**
   * Sets an expectation that OgContext will return the test group.
   */
  protected function expectGroupContext() {
    // OgContext::getGroup() will be called and is expected to return the test
    // group.
    $this
      ->ogContext
      ->getGroup()
      ->willReturn($this->group->reveal());
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
      ->getMembership($this->group->reveal(), $this->user->reveal(), $states)
      ->willReturn($state);
  }

}
