<?php

namespace Drupal\og\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgContextInterface;
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
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The OG context provider.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

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
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context provider.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   */
  public function __construct(AccountInterface $user, OgContextInterface $og_context, MembershipManagerInterface $membership_manager) {
    $this->user = $user;
    $this->ogContext = $og_context;
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
    // Do not provide a cache context if there is no group in the current
    // context.
    $group = $this->ogContext->getGroup();
    if (empty($group)) {
      return self::NO_CONTEXT;
    }

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->membershipManager->getMembership($group, $this->user->id(), OgMembershipInterface::ALL_STATES);
    return $membership ? $membership->getState() : self::NO_CONTEXT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
