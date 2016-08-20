<?php

namespace Drupal\og;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodePermissions;

/**
 * Provides dynamic groups permissions for node group content types.
 */
class OgNodePermissions extends NodePermissions {

  /**
   * Returns an array of node group content type permissions.
   *
   * @return array
   *   The node type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function nodeTypePermissions() {
    $perms = array();

    // Generate node permissions for all group content node types.
    foreach (NodeType::loadMultiple() as $bundle) {

      if (!Og::isGroupContent('node', $bundle->id())) {
        continue;
      }

      $perms += $this->buildPermissions($bundle);
    }

    return $perms;
  }

}
