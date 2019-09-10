<?php

namespace Drupal\og\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\og\OgAccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on group access for the current user.
 *
 * This is a general service that can be used to determine if a user has access
 * to a certain route.
 */
class GroupCheck implements AccessInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * Constructs a GroupCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OgAccessInterface $og_access) {
    $this->entityTypeManager = $entity_type_manager;
    $this->ogAccess = $og_access;
  }

  /**
   * Checks access by OG related permissions.
   *
   * If the route parameter names don't have {entity_type_id} or {entity_id} you
   * can still use this access check, by passing the "entity_type_id" definition
   * using the Route::setOption method.
   * see \Drupal\og\Routing\RouteSubscriber::alterRoutes as an example.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The currently logged in user.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The rout match object.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   * @param string $entity_id
   *   The entity ID. If the ID is not sent, the access method will try to
   *   extract it from the route matcher.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $user, Route $route, RouteMatchInterface $route_match, $entity_type_id = NULL, $entity_id = NULL) {
    $group = NULL;
    if (!$entity_type_id) {
      $parameter_name = $route_match->getRouteObject()->getOption('_og_entity_type_id');

      if (!$parameter_name) {
        throw new \BadMethodCallException('Group definition is missing from the router. Did you define $route->setOption(\'_og_entity_type_id\', $entity_type_id) in your route declaration?');
      }

      /** @var \Drupal\Core\Entity\EntityInterface $group */
      if (!$group = $route_match->getParameter($parameter_name)) {
        return AccessResult::forbidden();
      }

      $entity_type_id = $group->getEntityTypeId();
    }

    // No access if the entity type doesn't exist.
    if (!$this->entityTypeManager->getDefinition($entity_type_id, FALSE)) {
      return AccessResult::forbidden();
    }

    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $group = $group ?: $entity_storage->load($entity_id);

    // No access if no entity was loaded or it's not a group.
    if (!$group || !Og::isGroup($entity_type_id, $group->bundle())) {
      return AccessResult::forbidden();
    }

    // Iterate over the permissions.
    foreach (explode('|', $route->getRequirement('_og_user_access_group')) as $permission) {
      if ($this->ogAccess->userAccess($group, $permission, $user)->isAllowed()) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();

  }

}
