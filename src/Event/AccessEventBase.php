<?php

declare(strict_types = 1);

namespace Drupal\og\Event;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for OG access events.
 */
class AccessEventBase extends Event implements AccessEventInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The access result.
   *
   * @var \Drupal\Core\Access\AccessResultInterface
   */
  protected $access;

  /**
   * The group that provides the context for the access check.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $group;

  /**
   * The user for which to check access.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructs an AccessEventBase event.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group that provides the context in which to perform the access check.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user for which to check access.
   */
  public function __construct(ContentEntityInterface $group, AccountInterface $user) {
    $this->group = $group;
    $this->user = $user;
    $this->access = AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function grantAccess(): void {
    $this->access = $this->access->orIf(AccessResult::allowed());
  }

  /**
   * {@inheritdoc}
   */
  public function denyAccess(): void {
    $this->access = $this->access->orIf(AccessResult::forbidden());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): ContentEntityInterface {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(): AccountInterface {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessResult(): AccessResultInterface {
    $access = $this->access;

    // Enrich the access result object with our cacheability metadata in case it
    // supports it.
    if ($access instanceof RefinableCacheableDependencyInterface) {
      $access->addCacheableDependency($this);
    }

    return $access;
  }

}
