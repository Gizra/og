<?php

/**
 * @file
 * Contains \Drupal\og_ui\Access\GroupCheck.
 */

namespace Drupal\og_ui\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\og\OgAccess;
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
   * Constructs a GroupCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Route $route, $entity_type_id, $entity_id) {
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

    // @todo Convert when Role checking is added and has API to use.
    // Verify the bundle has roles
//    if (!og_roles($group_type, $bundle, $gid)) {
//      return AccessResult::forbidden();
//    }

    $permission = $permission = $route->getRequirement('_og_ui_user_access_group');

    return OgAccess::userAccess($group, $permission);
  }

}

