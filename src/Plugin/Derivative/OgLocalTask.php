<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\GroupTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 */
class OgLocalTask extends DeriverBase implements ContainerDeriverInterface {

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
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routProvider;

  /**
   * Creates an OgLocalTask object.
   *
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider services.
   */
  public function __construct(GroupTypeManagerInterface $group_type_manager, RouteProviderInterface $route_provider) {
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
      $route_name = "entity.$entity_type_id.og_admin_routes";

      if (!$this->routProvider->getRoutesByNames([$route_name])) {
        // Route not found.
        continue;
      }

      $derivatives[$entity_type_id . '.og_admin_routes'] = [
        'route_name' => $route_name,
        'title' => $this->t('Group'),
        'base_route' => 'entity.' . $entity_type_id . '.canonical',
        'weight' => 50,
      ];
    }

    // @todo Coder throws a false positive for this line. Remove this once the
    //   issue is fixed in the Coder project.
    // @see https://www.drupal.org/project/coder/issues/3065679
    // @codingStandardsIgnoreLine
    foreach ($derivatives as &$entry) {
      // @codingStandardsIgnoreLine
      $entry += $base_plugin_definition;
    }

    return $derivatives;
  }

}
