<?php

namespace Drupal\og;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for OgGroupResolver plugins that inspect the route.
 */
abstract class OgRouteGroupResolverBase extends OgGroupResolverBase implements ContainerFactoryPluginInterface {

  /**
   * The route matching service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * A list of all the link paths of enabled content entities.
   *
   * @var array
   */
  protected $contentEntityPaths;

  /**
   * Constructor for OgGroupResolver plugins that inspect that route.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, GroupTypeManagerInterface $group_type_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->groupTypeManager = $group_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('og.group_type_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the content entity from the current route.
   *
   * This will return the entity if the current route matches the entity paths
   * ('link templates') that are defined in the entity definition.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity, or NULL if we are not on a content entity path.
   */
  protected function getContentEntity() {
    $route = $this->routeMatch->getRouteObject();
    if (!$route) {
      return NULL;
    }
    // Check if we are on a content entity path.
    $path = $route->getPath();
    $paths = $this->getContentEntityPaths();
    if (array_key_exists($path, $paths)) {
      // Return the entity.
      return $this->routeMatch->getParameter($paths[$path]);
    }
    return NULL;
  }

  /**
   * Returns the paths for the link templates of all content entities.
   *
   * Based on LanguageNegotiationContentEntity::getContentEntityPaths().
   *
   * @return array
   *   An array of all content entity type IDs, keyed by the corresponding link
   *   template paths.
   *
   * @see \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity::getContentEntityPaths()
   */
  protected function getContentEntityPaths() {
    if (!isset($this->contentEntityPaths)) {
      $this->contentEntityPaths = [];
      /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
      $entity_types = $this->entityTypeManager->getDefinitions();
      foreach ($entity_types as $entity_type_id => $entity_type) {
        if ($entity_type->entityClassImplements(ContentEntityInterface::class)) {
          $entity_paths = array_fill_keys($entity_type->getLinkTemplates(), $entity_type_id);
          $this->contentEntityPaths = array_merge($this->contentEntityPaths, $entity_paths);
        }
      }
    }

    return $this->contentEntityPaths;
  }

}
