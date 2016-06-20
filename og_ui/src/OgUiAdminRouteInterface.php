<?php

namespace Drupal\og_ui;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityBase;

interface OgUiAdminRouteInterface {

  /**
   * @return ContentEntityBase
   */
  public function getGroup();

  /**
   * @param ContentEntityBase $group
   *
   * @return OgUiAdminRouteInterface
   */
  public function setGroup(ContentEntityBase $group);

  /**
   * Get the path of the admin.
   *
   * @return string
   */
  public function getPath();

  /**
   * Check if the current user can access to the plugin routes callback.
   *
   * @return AccessResultInterface
   */
  public function access();

  /**
   * Return list of defined sub-path of the plugin.
   *
   * @return array
   */
  public function routes();
  
}
