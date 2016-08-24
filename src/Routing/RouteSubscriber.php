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
   * @param \Drupal\og\OgAdminRoutesPluginManager $og_admin_routes_plugin_manager
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

      if ($og_task = $entity_type->getLinkTemplate('og-group-admin-pages')) {
        $entity_type_id = $entity_type->id();
        $route = new Route($og_task);

        $route
          ->addDefaults([
            '_controller' => '\Drupal\og\Controller\OgAdminController::mainPage',
            '_title' => 'Tasks',
          ])
          // @todo: Convert to service.
          ->addRequirements([
            '_custom_access' => '\Drupal\og\Access\OgGroupAdminAccess::access',
          ])
          ->setOption('parameters', [
            $entity_type_id => ['type' => 'entity:' . $entity_type_id],
          ])
          ->setOption('entity_type_id', $entity_type_id)
          ->setOption('_admin_route', TRUE);

        $collection->add('entity.' . $entity_type_id . '.og_group_admin_pages', $route);
      }
    }

    $this->createRoutesFromAdminRoutesPlugins($collection);
  }

  /**
   * Creating from OG tasks plugins.
   *
   * @param RouteCollection $collection
   *   The route collection object.
   */
  protected function createRoutesFromAdminRoutesPlugins(RouteCollection $collection) {
    foreach ($this->ogAdminRoutesPluginManager->getPlugins() as $plugin) {

      $definition = $plugin->getPluginDefinition() + [
        'access' => '\Drupal\og_ui\OgUiRoutesBase::access',
      ];

      // Iterate over all the parent routes.
      foreach ($definition['parents_routes'] as $entity_type_id => $parent_route) {

        if (!$this->route_provider->getRoutesByNames([$parent_route])) {
          $params = [
            '@router_name' => $parent_route,
            '@plugin_name' => '',
          ];
          \Drupal::logger('og_ui')->alert($this->t('The router @router_name, needed by @plugin_name, does not exists.', $params));
          continue;
        }

        $parent_path = $this->route_provider->getRouteByName($parent_route)->getPath();
        $path = $parent_path . '/group/' . $definition['path'];

        // Create a route for each route callback.
        foreach ($plugin->getRoutes() as $sub_route => $route_info) {
          $route = new Route($path . '/' . $route_info['sub_path']);
          $route
            ->addDefaults([
              '_controller' => $route_info['controller'],
              '_title' => $route_info['title'],
            ])
            ->addRequirements([
              '_custom_access' => $definition['access'],
              '_plugin_id' => $definition['id'],
            ])
            ->setOption('parameters', [
              $entity_type_id => ['type' => 'entity:' . $entity_type_id],
            ])
            ->setOption('entity_type_id', $entity_type_id)
            ->setOption('_admin_route', TRUE);

          $collection->add($parent_route . '.' . $definition['route_id'] . '.' . $sub_route, $route);
        }
      }
    }
  }

}
