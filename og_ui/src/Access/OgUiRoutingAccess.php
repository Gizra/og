<?php

namespace Drupal\og_ui\Access;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

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

    $parameters = \Drupal::routeMatch()->getParameters();
    $keys = $parameters->keys();

    /** @var ContentEntityBase $entity */
    $entity = $parameters->get(reset($keys));

    if (!is_object($entity)) {
      $path = explode('/', \Drupal::routeMatch()->getRouteObject()->getPath());
      $entity = \Drupal::entityTypeManager()->getStorage($path[1])->load($entity);
    }

    if (!\Drupal\og\Og::groupManager()->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      // Not a group. return.
      return AccessResultForbidden::forbidden();
    }

    return AccessResultAllowed::allowedIf($account->hasPermission('administer group'))->mergeCacheMaxAge(0);
  }
}
