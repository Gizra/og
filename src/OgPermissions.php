<?php

/**
 * @file
 * Contains \Drupal\og\OgPermissions.
 */

namespace Drupal\og;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodePermissions;

/**
 * Provides dynamic groups permissions for node group content types.
 */
class OgPermissions extends NodePermissions {

  /**
   * Returns an array of node group content type permissions.
   *
   * @return array
   *   The node type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function ogNodeTypesPermission() {
    $perms = array();

    // Generate node permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {

      if (!Og::isGroupContentType('node', $type)) {
        continue;
      }

      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

}
