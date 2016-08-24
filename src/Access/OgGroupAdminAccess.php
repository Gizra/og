<?php

namespace Drupal\og\Access;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgAdminRoutesPluginManager;

/**
 * Checks access for displaying configuration translation page.
 */
class OgGroupAdminAccess extends AccessResult implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return AccessResultInterface
   *   The access result object.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {

    /** @var \Drupal\og\GroupTypeManager $group_type_manager */
    $group_type_manager = \Drupal::service('og.group_type_manager');

    /** @var \Drupal\og\OgAdminRoutesPluginManager $og_admin_routes_plugin_manager */
    $og_admin_routes_plugin_manager = \Drupal::service('plugin.manager.og.group_admin_route');

    foreach ($route_match->getParameters() as $parameter) {
      if (!$parameter instanceof ContentEntityBase) {
        continue;
      }

      $entity = $parameter;
      if (!$group_type_manager->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
        // Entity is not a group.
        return self::forbidden();
      }
    }

    foreach ($og_admin_routes_plugin_manager->getPlugins() as $plugin) {
      if ($plugin->access($entity)) {
        return self::allowed();
      }
    }

    return self::forbidden();
  }

}
