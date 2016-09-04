<?php

namespace Drupal\og\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;

/**
 * Defines a cache context service, for "membership state" caching.
 *
 * Cache context ID: 'og_membership_state'
 */
class OgMembershipStateCacheContext implements CacheContextInterface {

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
    return t('Group membership');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if (!$route_object = $this->routeMatch->getRouteObject()) {
      return 'none';
    }

    if (!$route_contexts = $route_object->getOption('parameters')) {
      return 'none';
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
