<?php

namespace Drupal\og\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgAccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on group access for the current user.
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
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The currently logged in user.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param string $entity_type_id
   *   The entity type.
   * @param string $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $user, Route $route, $entity_type_id, $entity_id) {
    // No access if the entity type doesn't exist.
    if (!$this->entityTypeManager->getDefinition($entity_type_id, FALSE)) {
      return AccessResult::forbidden();
    }

    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $group = $entity_storage->load($entity_id);

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
