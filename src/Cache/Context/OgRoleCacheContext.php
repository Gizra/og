<?php

namespace Drupal\og\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgRoleInterface;

/**
 * Defines a cache context service for the OG roles of the current user.
 *
 * This cache context allows to cache render elements that vary by the role of
 * the user within the available group(s). This is useful for elements that for
 * example display information that is only intended for group members or
 * administrators.
 *
 * A user might have multiple roles in a group, this is also taken into account
 * when calculating the cache context key.
 *
 * Since the user might be a member of a large number of groups this cache
 * context key is presented as a hashed value.
 *
 * Cache context ID: 'og_role'
 */
class OgRoleCacheContext extends UserCacheContextBase implements CacheContextInterface {

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
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * An array of cached cache context key hashes.
   *
   * @var string[]
   */
  protected $hashes = [];

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('OG role');
  }

  /**
   * Constructs a new UserCacheContextBase class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(AccountInterface $user, MembershipManagerInterface $membership_manager, PrivateKey $private_key) {
    parent::__construct($user);

    $this->membershipManager = $membership_manager;
    $this->privateKey = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Due to cacheability metadata bubbling this can be called often. Only
    // compute the hash once.
    if (empty($this->hashes[$this->user->id()])) {
      $memberships = [];
      foreach ($this->membershipManager->getMemberships($this->user->id()) as $membership) {
        $role_names = array_map(function (OgRoleInterface $role) {
          return $role->getName();
        }, $membership->getRoles());
        if ($role_names) {
          $memberships[$membership->getGroupEntityType()][$membership->getGroupId()] = $role_names;
        }
      }

      // Sort the memberships, so that the same key can be generated, even if
      // the memberships were defined in a different order.
      ksort($memberships);
      foreach ($memberships as $entity_type_id => &$groups) {
        ksort($groups);
        foreach ($groups as $group_id => &$role_names) {
          sort($role_names);
        }
      }

      // If the user is not a member of any groups, return a unique key.
      $this->hashes[$this->user->id()] = empty($memberships) ? self::NO_CONTEXT : $this->hash(serialize($memberships));
    }

    return $this->hashes[$this->user->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

}
