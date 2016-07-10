<?php

namespace Drupal\og_ui\Access;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;

/**
 * Checks access for displaying configuration translation page.
 */
class OgUiRoutingAccess implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return AccessResultAllowed
   */
  public function GroupTabAccess(AccountInterface $account) {

    $route_match = \Drupal::routeMatch();
    $parameters = $route_match->getParameters();
    $keys = $parameters->keys();

    /** @var ContentEntityBase $entity */
    $entity = $parameters->get(reset($keys));

    if (!is_object($entity)) {
      $path = explode('/', $route_match->getRouteObject()->getPath());
      $entity = \Drupal::entityTypeManager()->getStorage($path[1])->load($entity);
    }

    if (!Og::groupManager()->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      // Not a group. return.
      return AccessResultForbidden::forbidden();
    }

    // todo: fix. us the access callback.
    return AccessResultAllowed::allowedIf($account->hasPermission('administer group'))->mergeCacheMaxAge(0);
  }
}
