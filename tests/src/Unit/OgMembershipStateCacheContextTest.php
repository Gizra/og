<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Cache\Context\OgMembershipStateCacheContext;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests OG membership state cache context.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Cache\Context\OgMembershipStateCacheContext
 */
class OgMembershipStateCacheContextTest extends UnitTestCase {


  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The group type manager service.
   *
   * @var \Drupal\og\GroupTypeManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;


  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * The route service.
   *
   * @var \Symfony\Component\Routing\Route|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $route;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $user;

  /**
   * The group entity type IDs.
   *
   * @var array
   */
  protected $groupEntities;

  /**
   * Array with the route parameters.
   *
   * @var array
   */
  protected $parameters;

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
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);

    $this->group = $this->prophesize(EntityInterface::class);
    $this->groupTypeManager = $this->prophesize(GroupTypeManager::class);

    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);

    $this->route = $this->prophesize(Route::class);

    $this->entityTypeId = $this->randomMachineName();

    $this->groupEntities = [
      $this->entityTypeId => [$this->randomMachineName()],
      $this->randomMachineName() => [$this->randomMachineName()],
    ];

    $this->parameters = [
      $this->entityTypeId => [$this->randomMachineName()],
      $this->randomMachineName() => [$this->randomMachineName()],
    ];
  }

  /**
   * Tests getting context of a route with no parameters.
   *
   * @covers ::getContext
   */
  public function testRouteWithNoParameters() {
    $this
      ->routeMatch
      ->getRouteObject()
      ->willReturn($this->route->reveal());

    $this
      ->route
      ->getOption('parameters')
      ->willReturn(NULL);

    $result = $this->getContextResult();
    $this->assertEquals('none', $result);
  }

  /**
   * Tests getting context when there are no group entities defined.
   *
   * @covers ::getContext
   */
  public function testNoGroupEntities() {
    $this
      ->routeMatch
      ->getRouteObject()
      ->willReturn($this->route->reveal());

    $this
      ->route
      ->getOption('parameters')
      ->willReturn($this->parameters);

    $this
      ->groupTypeManager
      ->getAllGroupBundles()
      ->willReturn([]);

    $result = $this->getContextResult();
    $this->assertEquals('none', $result);
  }

  /**
   * Tests getting context when there are matching group entities in the route.
   *
   * @covers ::getContext
   */
  public function testNoGroupAndRouteParametersIntersection() {
    $this
      ->routeMatch
      ->getRouteObject()
      ->willReturn($this->route->reveal());

    $this
      ->route
      ->getOption('parameters')
      ->willReturn($this->parameters);

    $group_entities = [
      $this->randomMachineName() => [$this->randomMachineName()],
    ];

    $this
      ->groupTypeManager
      ->getAllGroupBundles()
      ->willReturn($group_entities);

    $result = $this->getContextResult();
    $this->assertEquals('none', $result);
  }

  /**
   * Tests user with no membership.
   *
   * @covers ::getContext
   */
  public function testNoMembership() {
    $this
      ->routeMatch
      ->getRouteObject()
      ->willReturn($this->route->reveal());

    $this
      ->route
      ->getOption('parameters')
      ->willReturn($this->parameters);

    $this
      ->groupTypeManager
      ->getAllGroupBundles()
      ->willReturn($this->groupEntities);

    $this
      ->routeMatch
      ->getParameter($this->entityTypeId)
      ->willReturn($this->group->reveal());

    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    $this
      ->membershipManager
      ->getMembership($this->group->reveal(), $this->user->reveal(), $states)
      ->willReturn(FALSE);

    $result = $this->getContextResult();
    $this->assertEquals('none', $result);

  }

  /**
   * Return the context result.
   *
   * @return string
   *   The context result.
   */
  protected function getContextResult() {
    $cache_context = new OgMembershipStateCacheContext($this->user->reveal(), $this->routeMatch->reveal(), $this->groupTypeManager->reveal(), $this->membershipManager->reveal());
    return $cache_context->getContext();
  }

}
