<?php

/**
 * @file
 * Contains \Drupal\og\OgPermissionHandler.
 */

namespace Drupal\og;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\user\PermissionHandler;
use Drupal\user\PermissionHandlerInterface;

/**
 * Provides permissions for groups based on YNL files.
 *
 * The permissions file should be formatted by the next format(with comments):
 * @code
 * # The key is the permission machine name, and is required.
 * update group:
 *   # (required) Human readable name of the permission used in the UI.
 *   title: 'Edit group'
 *   # (optional) Additional description fo the permission used in the UI.
 *   description: 'Edit the group. Note: This permission controls only node entity type groups.'
 *   # (optional) Boolean, when set to true a warning about site security will
 *   # be displayed on the Permissions page. Defaults to false.
 *   restrict access: false
 *   # Determine to which roles the permissions will be enabled by default.
 *   'default role':
 *     - OG_ADMINISTRATOR_ROLE
 * @endcode
 *
 * @see \Drupal\user\PermissionHandler
 */
class OgPermissionHandler extends PermissionHandler implements PermissionHandlerInterface {

  /**
   * {@inheritdoc}
   */
  protected function getYamlDiscovery() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('og_permissions', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery;
  }

}
