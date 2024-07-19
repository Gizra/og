<?php

declare(strict_types=1);

namespace Drupal\og\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\GroupTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides action link definitions for all entity bundles.
 */
class OgActionLink extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * Route provider object.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * Creates an OgLocalTask object.
   *
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   *   The route provider services.
   */
  public function __construct(GroupTypeManager $group_type_manager, RouteProvider $route_provider) {
    $this->groupTypeManager = $group_type_manager;
    $this->routeProvider = $route_provider;
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
      $route_name = "entity.$entity_type_id.og_admin_routes.add_membership_page";

      if (!$this->routeProvider->getRoutesByNames([$route_name])) {
        // Route not found.
        continue;
      }

      $derivatives["og_membership.$entity_type_id.add"] = [
        'route_name' => $route_name,
        'title' => $this->t('Add a member'),
        'appears_on' => ["entity.$entity_type_id.og_admin_routes.members"],
      ];
    }

    foreach ($derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $derivatives;
  }

}
