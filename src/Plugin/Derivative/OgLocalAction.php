<?php

namespace Drupal\og\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\GroupTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OgLocalAction extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * Route provider object.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routProvider;

  /**
   * Creates an OgLocalTask object.
   *
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider services.
   */
  public function __construct(GroupTypeManagerInterface $group_type_manager, RouteProvider $route_provider) {
    $this->groupTypeManager = $group_type_manager;
    $this->routProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('og.group_type_manager'),
      $container->get('router.route_provider')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $derivatives = [];

    foreach (array_keys($this->groupTypeManager->getGroupMap()) as $entity_type_id) {
      $route_name = "entity.$entity_type_id.og_admin_routes.add_member";
      $appears_route = "entity.$entity_type_id.og_admin_routes.members";
      if (!$this->routProvider->getRoutesByNames([$route_name, $appears_route])) {
        // Routes not found.
        continue;
      }

      $derivatives["$entity_type_id.add_member"] = [
        'route_name' => $route_name,
        'title' => $this->t('Add member'),
        'appears_on' => [$appears_route],
      ];
    }

    foreach ($derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $derivatives;
  }
}
