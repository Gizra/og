<?php

namespace Drupal\og\Plugin\OgGroupResolver;

use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\OgRouteGroupResolverBase;

/**
 * Resolves the group from the route.
 *
 * This plugin inspects the current route and checks if it is an entity path for
 * a group entity.
 *
 * @OgGroupResolver(
 *   id = "route_group",
 *   label = "Group entity from current route",
 *   description = @Translation("Checks if the current route is an entity path that belongs to a group entity.")
 * )
 */
class RouteGroupResolver extends OgRouteGroupResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    $entity = $this->getContentEntity();
    if ($entity && $this->groupTypeManager->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      $collection->addGroup($entity, ['route']);

      // We are on a route that matches an entity path for a group entity. We
      // can conclude with 100% certainty that this group is relevant for the
      // current context. There's no need to keep searching.
      $this->stopPropagation();
    }
  }

}
