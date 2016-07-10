<?php

namespace Drupal\og_ui\Access;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\og_ui\OgUi;

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

    $entity = OgUi::getEntity();

    if (!Og::groupManager()->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      // Not a group. return.
      return AccessResultForbidden::forbidden();
    }

    $plugins = OgUi::getGroupAdminPlugins();

    $found = FALSE;
    foreach ($plugins as $plugin) {
      // We need at least one plugin which the user have access to.
      if (AccessResultAllowed::allowedIf($plugin->setGroup($entity)->access())->mergeCacheMaxAge(0)) {
        $found = true;
        continue;
      }
    }

    return AccessResultAllowed::allowedIf($found)->mergeCacheMaxAge(0);
  }

}
