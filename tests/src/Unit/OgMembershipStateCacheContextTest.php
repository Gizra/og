<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Cache\Context\OgMembershipStateCacheContext;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\Plugin\Derivative\OgLocalTask;
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
   * {@inheritdoc}
   */
  public function setUp() {
    $this->user = $this->prophesize(AccountInterface::class);;
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);;
    $this->groupTypeManager = $this->prophesize(GroupTypeManager::class);
    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);


    $this->route = $this->prophesize(Route::class);
//    // Set the container for the string translation service.
//    $translation = $this->getStringTranslationStub();
//    $container = new ContainerBuilder();
//    $container->set('string_translation', $translation);
//    \Drupal::setContainer($container);
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


    $cache_context = new OgMembershipStateCacheContext($this->user->reveal(), $this->routeMatch->reveal(), $this->groupTypeManager->reveal(), $this->membershipManager->reveal());
    $result = $cache_context->getContext();

    $this->assertEquals('none', $result);
  }

}
