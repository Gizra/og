<?php

namespace Drupal\og\Routing;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\OgAdminRoutesPluginManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
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
   * The OG Admin plugin manager.
   *
   * @var \Drupal\og\Routing\OgAdminRoutesPluginManager
   */
  protected $ogAdminRoutesPluginManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param EntityTypeManager $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider service.
   * @param \Drupal\og\OgAdminRoutesPluginManager $og_admin_routes_plugin_manager
   *   The OG Admin plugin manager.
   */
  public function __construct(EntityTypeManager $entity_manager, RouteProvider $route_provider, OgAdminRoutesPluginManager $og_admin_routes_plugin_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->routeProvider = $route_provider;
    $this->ogAdminRoutesPluginManager = $og_admin_routes_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {

      if (!$og_admin_path = $entity_type->getLinkTemplate('og-group-admin-pages')) {
        // Entity type doesn't have the link template defined.
        continue;
      }

      $entity_type_id = $entity_type->id();
      $route = new Route($og_admin_path);

      $route
        ->addDefaults([
          '_controller' => '\Drupal\og\Controller\OgAdminController::mainPage',
          '_title' => 'Group management',
        ])
        ->addRequirements([
          '_custom_access' => '\Drupal\og\Access\OgGroupAdminAccess::access',
        ])
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ])
        ->setOption('_admin_route', TRUE);

      $collection->add('entity.' . $entity_type_id . '.og_admin', $route);

      // Add the plugins routes.
      $this->createRoutesForOgAdminPlugins($og_admin_path, $entity_type_id, $collection);

    }

  }

  /**
   * Add all the OG admin plugins to the route collection.
   *
   * @param string $og_admin_path
   *   The OG admin path.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection object.
   */
  protected function createRoutesForOgAdminPlugins($og_admin_path, $entity_type_id, RouteCollection $collection) {
    foreach ($this->ogAdminRoutesPluginManager->getPlugins() as $plugin) {

      $plugin_definition = $plugin->getPluginDefinition();
      $plugin_id = $plugin_definition['id'];

      // Add the parent route.
      $parent_route_name = "entity.$entity_type_id.og_admin.$plugin_id";
      $parent_path = $og_admin_path . '/' . $plugin_definition['path'];

      $info = [
        'controller' => $plugin_definition['controller'],
        'title' => $plugin_definition['title'],
      ];

      $this->addRoute($collection, $entity_type_id, $parent_route_name, $parent_path, $info);


      // Add the sub routes.
      foreach ($plugin->getSubRoutes() as $name => $route_info) {
        $route_name = $parent_route_name . '.' . $name;
        $path = $parent_path . '/' . $info['path'];

        $info = [
          'controller' => $route_info['controller'],
          'title' => $route_info['title'],
        ];

        $this->addRoute($collection, $entity_type_id, $route_name, $path, $info);
      }
    }
  }

  /**
   * Add route to collection
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection route.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $route_name
   *   The route name.
   * @param string $path
   *   The route path.
   * @param array $info
   *   Array with the router definitions. Required keys are:
   *   - controller
   *   - title
   */
  protected function addRoute(RouteCollection $collection, $entity_type_id, $route_name, $path, array $info) {
    $route = new Route($path);
    $route
      ->addDefaults([
        '_controller' => $info['controller'],
        '_title' => $info['title'],
      ])
      ->addRequirements([
        // @todo: Allow to specify a callback instead of a permission.
        '_og_user_access_group' => 'administer group',
      ])
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ])
      // @todo: We might need to define own admin route, like node module to
      // prevent access denied?
      ->setOption('_admin_route', TRUE);

    $collection->add($route_name, $route);
  }

}
