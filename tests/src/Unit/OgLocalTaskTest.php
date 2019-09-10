<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteProvider;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\Plugin\Derivative\OgLocalTask;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests OG local task definition.
 *
 * Assert that the "Group" tab is properly added.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Derivative\OgLocalTask
 */
class OgLocalTaskTest extends UnitTestCase {

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;

  /**
   * Route provider object.
   *
   * @var \Drupal\Core\Routing\RouteProvider|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeProvider;

  /**
   * The route service.
   *
   * @var \Symfony\Component\Routing\Route|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $route;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->groupTypeManager = $this->prophesize(GroupTypeManagerInterface::class);
    $this->routeProvider = $this->prophesize(RouteProvider::class);
    $this->route = $this->prophesize(Route::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests setting local task definitions.
   *
   * @covers ::getDerivativeDefinitions
   */
  public function testGetDerivativeDefinitions() {
    $entity_type_id1 = $this->randomMachineName();
    $entity_type_id2 = $this->randomMachineName();

    $group_map = [
      // We don't need to add the bundles data, as they are skipped by doing
      // array_keys() on the tested method.
      $entity_type_id1 => [],
      $entity_type_id2 => [],
    ];

    $this
      ->groupTypeManager
      ->getGroupMap()
      ->willReturn($group_map);

    foreach (array_keys($group_map) as $entity_type_id) {
      $route_name = "entity.$entity_type_id.og_admin_routes";

      $this
        ->routeProvider
        ->getRoutesByNames([$route_name])
        ->willReturn([
          $this->route->reveal(),
          $this->route->reveal(),
        ]);
    }

    $og_local_task = new OgLocalTask($this->groupTypeManager->reveal(), $this->routeProvider->reveal());
    $derivatives = $og_local_task->getDerivativeDefinitions([]);

    $this->assertCount(2, $derivatives);

  }

}
