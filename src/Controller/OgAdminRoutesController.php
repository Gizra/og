<?php

namespace Drupal\og\Controller;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\og\Event\OgAdminRoutesEvent;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The OG admin routes controller.
 */
class OgAdminRoutesController extends ControllerBase {

  /**
   * The event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs an OgAdminController object.
   *
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ContainerAwareEventDispatcher $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * Show all the available admin routes.
   *
   * @return mixed
   *   List of available admin routes for the current group.
   */
  public function overview($entity_type_id) {
    // Get list from routes.
    $content = [];

    $event = new OgAdminRoutesEvent();
    $this->eventDispatcher->dispatch(OgAdminRoutesEventInterface::EVENT_NAME, $event);

    foreach ($event->getRoutes() as $name => $info) {
      $route_name = "entity.$entity_type_id.og_admin_routes.$name";

      // @todo: How the get the id?
      $route = Url::fromRoute($route_name, [$entity_type_id => "3"]);

      if (!$route->access()) {
        // User doesn't have access to the route.
        continue;
      }

      $content[$name]['title'] = $info['title'];
      $content[$name]['description'] = $info['description'];
      $content[$name]['url'] = $route;
    }

    return [
      'og_admin_routes' => [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ],
    ];
  }

}
