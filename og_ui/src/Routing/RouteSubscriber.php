<?php

/**
 * @file
 * Contains \Drupal\example\Routing\RouteSubscriber.
 */

namespace Drupal\og_ui\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {

      if ($og_task = $entity_type->getLinkTemplate('og-group-admin-pages')) {
        $entity_type_id = $entity_type->id();
        $route = new Route($og_task);

        $route
          ->addDefaults([
            '_controller' => '\Drupal\og_ui\Controller\OgUiController::ogTasks',
            '_title' => 'Tasks',
          ])
          ->addRequirements([
            '_custom_access' => '\Drupal\og_ui\Access\OgUiRoutingAccess::GroupTabAccess',
          ])
          ->setOption('_admin_route', TRUE);

        $collection->add('entity.' . $entity_type_id . '.og_group_admin_pages', $route);
      }
    }

    $this->createRoutesFromAdminRoutesPlugins($collection);
  }

  protected function createRoutesFromAdminRoutesPlugins(RouteCollection $collection) {
//    print_r(Url::fromRoute('entity.node.canonical')->getInternalPath());
    /** @var RouteProvider $route_provider */
    $route_provider = \Drupal::getContainer()->get('router.route_provider');

    $node_path = $route_provider->getRouteByName('entity.node.canonical')->getPath();

    $route = new Route($node_path . '/group/people');

    // tbd.
    $route
      ->addDefaults([
        '_controller' => '\Drupal\og_ui\Controller\OgUiController::ogTasks',
        '_title' => 'Tasks',
      ])
      ->addRequirements([
        '_custom_access' => '\Drupal\og_ui\Access\OgUiRoutingAccess::GroupTabAccess',
      ])
      ->setOption('_admin_route', TRUE);

    $collection->add('foo', $route);
  }

}
