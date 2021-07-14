<?php

declare(strict_types = 1);

namespace Drupal\og\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgMembershipTypeInterface;

/**
 * Check if a user has access to the group's membership pages.
 */
class OgMembershipAddAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to create the entity type and bundle for the given route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\og\OgMembershipTypeInterface $og_membership_type
   *   The membership type entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, EntityInterface $group = NULL, OgMembershipTypeInterface $og_membership_type = NULL) {
    // The $group param will be null if it is from the
    // Drupal\og\Event\OgAdminRoutesEvent rather than the routing.yml version.
    if (is_null($group)) {
      $entity_type_id = $route_match->getRouteObject()
        ->getOption('_og_entity_type_id');
      $group = $route_match->getParameter($entity_type_id);
    }

    if (!Og::isGroup($group->getEntityTypeId(), $group->bundle())) {
      return AccessResult::forbidden();
    }

    $membership_type_id = OgMembershipInterface::TYPE_DEFAULT;
    if (!is_null($og_membership_type)) {
      $membership_type_id = $og_membership_type->id();
    }

    $context = ['group' => $group];

    return $this->entityTypeManager
      ->getAccessControlHandler('og_membership')
      ->createAccess($membership_type_id, $account, $context, TRUE);
  }

}
