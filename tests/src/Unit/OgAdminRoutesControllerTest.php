<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\og\Controller\OgAdminRoutesController;
use Drupal\og\Event\OgAdminRoutesEvent;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests the OG admin routes overview route.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Controller\OgAdminRoutesController
 */
class OgAdminRoutesControllerTest extends UnitTestCase {

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager|\Prophecy\Prophecy\ObjectProphecy
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
   * {@inheritdoc}
   */
  public function setUp() {

    // @todo: Change PHPdocs.
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);

    $this->group = $this->prophesize(EntityInterface::class);
    $this->event = $this->prophesize(OgAdminRoutesEvent::class);
    $this->eventDispatcher = $this->prophesize(ContainerAwareEventDispatcher::class);
    $this->route = $this->prophesize(Route::class);
    $this->entityTypeId = $this->randomMachineName();
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

    $event = new OgAdminRoutesEvent();
    $this
      ->eventDispatcher
      ->dispatch(OgAdminRoutesEventInterface::EVENT_NAME, $event)
      ->shouldBeCalled();

    $this
      ->event
      ->getRoutes($this->entityTypeId)
      ->willReturn($this->routesInfo);

    // Set the container for the string translation service.
    $translation = $this->getStringTranslationStub();
    $container = new ContainerBuilder();
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);
  }

  /**
   * Tests overview with non-accessible routes.
   *
   * @covers ::overview
   */
  public function testRoutesWithNoAccess() {
    $this
      ->url
      ->access()
      ->willReturn(FALSE);

    $og_admin_routes_controller = new OgAdminRoutesController($this->eventDispatcher->reveal());
    $result = $og_admin_routes_controller->overview($this->routeMatch->reveal());

    $this->assertEquals('You do not have any administrative items.', $result['#markup']);
  }

}
