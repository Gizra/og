<?php

namespace Drupal\og\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;

/**
 * Defines a cache context service, for "membership state" caching.
 *
 * Cache context ID: 'og_membership_state'
 */
class OgMembershipStateCacheContext implements CacheContextInterface {

  /**
   * The string to return when no context is found.
   */
  const NO_CONTEXT = 'none';


  /**
   * The group type manager service.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;


  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs a new UserCacheContextBase class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The group type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   */
  public function __construct(AccountInterface $user, RouteMatchInterface $route_match, GroupTypeManager $group_type_manager, MembershipManagerInterface $membership_manager) {
    $this->user = $user;
    $this->routeMatch = $route_match;
    $this->groupTypeManager = $group_type_manager;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('OG membership state');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if (!$route_contexts = $this->routeMatch->getRouteObject()->getOption('parameters')) {
      // No "parameters" defined in the route.
      return self::NO_CONTEXT;
    }

    if (!$entity_type_ids = array_keys($this->groupTypeManager->getAllGroupBundles())) {
      // No group entities.
      return self::NO_CONTEXT;
    }

    if (!$entity_type_ids = array_intersect(array_keys($route_contexts), $entity_type_ids)) {
      // No parameters that match the group entities.
      return self::NO_CONTEXT;
    }

    // Take just the first entity type ID.
    $entity_type_id = reset($entity_type_ids);

    $group = $this->routeMatch->getParameter($entity_type_id);
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ];

    /** @var OgMembershipInterface $membership */
    $membership = $this->membershipManager->getMembership($group, $this->user, $states);
    return $membership ? $membership->getState() : self::NO_CONTEXT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
