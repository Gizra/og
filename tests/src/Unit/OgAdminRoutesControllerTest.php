<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\og\Controller\OgAdminRoutesController;
use Drupal\og\Event\OgAdminRoutesEvent;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Route;

/**
 * Tests the OG admin routes overview route.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Controller\OgAdminRoutesController
 */
class OgAdminRoutesControllerTest extends UnitTestCase {

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $accessManager;

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
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeMatch;

  /**
   * The event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $eventDispatcher;

  /**
   * The group entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $group;

  /**
   * The OG admin route event.
   *
   * @var \Drupal\og\Event\OgAdminRoutesEvent
   */
  protected $event;

  /**
   * The entity type ID of the group entity.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The routes info as returned from the event subscribers.
   *
   * @var array
   */
  protected $routesInfo;

  /**
   * The Url object.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * The entity ID.
   *
   * @var int
   */
  protected $entityId;

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    $this->accessManager = $this->prophesize(AccessManagerInterface::class);
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);

    $this->group = $this->prophesize(EntityInterface::class);
    $this->event = $this->prophesize(OgAdminRoutesEvent::class);
    $this->eventDispatcher = $this->prophesize(ContainerAwareEventDispatcher::class);
    $this->route = $this->prophesize(Route::class);
    $this->entityTypeId = $this->randomMachineName();
    $this->entityId = rand(20, 30);
    $this->url = $this->prophesize(Url::class);

    $this->routesInfo = [
      $this->randomMachineName() => [
        'title' => $this->randomMachineName(),
        'description' => $this->randomMachineName(),
      ],

      $this->randomMachineName() => [
        'title' => $this->randomMachineName(),
        'description' => $this->randomMachineName(),
      ],
    ];

    $this
      ->routeMatch
      ->getRouteObject()
      ->willReturn($this->route);

    $parameter_name = $this->randomMachineName();

    $this
      ->route
      ->getOption('_og_entity_type_id')
      ->willReturn($parameter_name);

    $this
      ->routeMatch
      ->getParameter($parameter_name)
      ->willReturn($this->group->reveal());

    $this
      ->group
      ->getEntityTypeId()
      ->willReturn($this->entityTypeId);

    $this
      ->group
      ->id()
      ->willReturn($this->entityId);

    $this
      ->eventDispatcher
      ->dispatch(OgAdminRoutesEventInterface::EVENT_NAME, Argument::type(OgAdminRoutesEvent::class))
      ->willReturn($this->event->reveal())
      ->shouldBeCalled();

    $this
      ->event
      ->getRoutes($this->entityTypeId)
      ->willReturn($this->routesInfo)
      ->shouldBeCalled();

    // Set the container for the string translation service.
    $translation = $this->getStringTranslationStub();
    $container = new ContainerBuilder();
    $container->set('access_manager', $this->accessManager->reveal());
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);
  }

  /**
   * Tests overview with non-accessible routes.
   *
   * @covers ::overview
   */
  public function testRoutesWithNoAccess() {
    $result = $this->getRenderElementResult(FALSE);
    $this->assertEquals('You do not have any administrative items.', $result['#markup']);
  }

  /**
   * Tests overview with accessible routes.
   *
   * @covers ::overview
   */
  public function testRoutesWithAccess() {
    $result = $this->getRenderElementResult(TRUE);

    foreach ($result['og_admin_routes']['#content'] as $key => $value) {
      $this->assertEquals($this->routesInfo[$key]['title'], $value['title']);
      $this->assertEquals($this->routesInfo[$key]['description'], $value['description']);
    }

  }

  /**
   * Return the render array from calling the "overview" method.
   *
   * @param bool $allow_access
   *   Indicate of access to the routes should be given.
   *
   * @return array
   *   The render array.
   */
  protected function getRenderElementResult($allow_access) {
    $parameters = [$this->entityTypeId => $this->entityId];
    foreach (array_keys($this->routesInfo) as $name) {
      $route_name = "entity.{$this->entityTypeId}.og_admin_routes.$name";
      $this
        ->accessManager
        ->checkNamedRoute($route_name, $parameters)
        ->willReturn($allow_access);
    }

    $og_admin_routes_controller = new OgAdminRoutesController($this->eventDispatcher->reveal(), $this->accessManager->reveal());
    return $og_admin_routes_controller->overview($this->routeMatch->reveal());
  }

}
