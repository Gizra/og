<?php

namespace Drupal\og\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\OgAdminRoutesEvent;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber for OG related routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route provider service.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteProviderInterface $route_provider, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {

      if (!$entity_type->hasLinkTemplate('canonical')) {
        // Entity type doesn't have a canonical route.
        continue;
      }

      if (!$og_admin_path = $entity_type->getLinkTemplate('og-admin-routes')) {
        // Entity type doesn't have the link template defined.
        continue;
      }

      $entity_type_id = $entity_type->id();
      $route_name = "entity.$entity_type_id.og_admin_routes";
      $route = new Route($og_admin_path);

      $route
        ->addDefaults([
          '_controller' => '\Drupal\og\Controller\OgAdminRoutesController::overview',
          '_title' => 'Group management',
        ])
        ->addRequirements([
          '_og_user_access_group' => 'administer group',
        ])
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ])
        // As the above parameters doesn't send the entity,
        // so we will have to use the Route matcher to extract it.
        ->setOption('_og_entity_type_id', $entity_type_id)
        ->setOption('_admin_route', TRUE);

      $collection->add($route_name, $route);

      // Add the routes defined in the event subscribers.
      $this->createRoutesFromEventSubscribers($og_admin_path, $entity_type_id, $collection);

    }

  }

  /**
   * Add all the OG admin items to the route collection.
   *
   * @param string $og_admin_path
   *   The OG admin path.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection object.
   */
  protected function createRoutesFromEventSubscribers($og_admin_path, $entity_type_id, RouteCollection $collection) {
    $event = new OgAdminRoutesEvent();
    $this->eventDispatcher->dispatch(OgAdminRoutesEventInterface::EVENT_NAME, $event);

    foreach ($event->getRoutes($entity_type_id) as $name => $route_info) {
      // Add the parent route.
      $parent_route_name = "entity.$entity_type_id.og_admin_routes.$name";
      $parent_path = $og_admin_path . '/' . $route_info['path'];

      $this->addRoute($collection, $parent_route_name, $parent_path, $route_info);
    }
  }

  /**
   * Helper method to add route to collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection route.
   * @param string $route_name
   *   The route name.
   * @param string $path
   *   The route path.
   * @param array $route_info
   *   Array with the router definitions. Required keys are "defaults",
   *   "options", and "requirements".
   */
  protected function addRoute(RouteCollection $collection, $route_name, $path, array $route_info) {
    $route = new Route($path);
    $route
      ->addDefaults($route_info['defaults'])
      ->addRequirements($route_info['requirements'])
      ->addOptions($route_info['options']);

    $collection->add($route_name, $route);
  }

  /**
   * Overrides \Drupal\Core\Routing\RouteSubscriberBase::getSubscribedEvents.
   *
   * See the event weight so it will be executed before other alters, such
   * as \Drupal\Core\EventSubscriber\ModuleRouteSubscriber::alterRoutes which
   * is responsible for removing routes that their dependent module is not
   * enabled.
   *
   * We have such a case with the "members" OG admin route, that requires Views
   * module to be enabled.
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }

}
