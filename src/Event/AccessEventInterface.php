<?php

declare(strict_types = 1);

namespace Drupal\og\Event;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for events that determine access in Organic Groups.
 */
interface AccessEventInterface extends RefinableCacheableDependencyInterface {

  /**
   * Declare that access is being granted.
   *
   * Calling this method will cause access to be granted for the action that is
   * being checked, unless another event listener denies access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The updated access result.
   */
  public function grantAccess(): AccessResultInterface;

  /**
   * Declare that access is being denied.
   *
   * Calling this method will cause access to be denied for the action that is
   * being checked. This takes precedence over any other event listeners that
   * might grant access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The updated access result.
   */
  public function denyAccess(): AccessResultInterface;

  /**
   * Merges the given access result with the existing access result.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The updated access result.
   */
  public function mergeAccessResult(AccessResultInterface $access_result): AccessResultInterface;

  /**
   * Returns the group that provides the context for the access check.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The group entity.
   */
  public function getGroup(): ContentEntityInterface;

  /**
   * Returns the user for which access is being determined.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user.
   */
  public function getUser(): AccountInterface;

  /**
   * Returns the current access result object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function getAccessResult(): AccessResultInterface;

}
