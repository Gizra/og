<?php

namespace Drupal\og\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to for node add pages.
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
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $og_membership_type = $route_match->getParameter('membership_type');
    if (is_object($og_membership_type)) {
      $og_membership_type = $og_membership_type->id();
    }

    if ($entity_type_id = $route_match->getParameter('entity_type_id')) {
      $group = $this->entityTypeManager
        ->getStorage($route_match->getParameter('entity_type_id'))
        ->load($route_match->getParameter('group'));
    }
    else {
      $entity_type_id = $route->getOption('_og_entity_type_id');
      $group = $route_match->getParameter($entity_type_id);
    }

    $context = ['group' => $group];

    return $this->entityTypeManager
      ->getAccessControlHandler('og_membership')
      ->createAccess($og_membership_type, $account, $context, TRUE);
  }

}
