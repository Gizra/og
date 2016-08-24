<?php

namespace Drupal\og;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the OG admin plugins.
 */
interface OgAdminRouteInterface extends PluginInspectionInterface {

  /**
   * Check if the current user can access to the plugin routes callback.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result object.
   */
  public function access(ContentEntityInterface $group);

  /**
   * Return the parent route.
   *
   * Every plugin must have at least a single route, which we call the parent
   * route. Below it there may be other sub-routes. For example in the "People"
   * plugin, the parent route is the page that shows the member management
   * table and is under /members. However, that plugin also exposes other
   * routes, such as the "add member" route which will be under
   *  /members/add-member.
   *
   * @return array
   *   Array with the route definition.
   */
  public function getParentRoute();

  /**
   * Return list of defined sub-path of the plugin.
   *
   * @return array
   *   List of routes.
   */
  public function getSubRoutes();

}
