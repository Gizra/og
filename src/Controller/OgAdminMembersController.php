<?php

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;

/**
 * OgAdminMembersController class.
 */
class OgAdminMembersController extends ControllerBase {

  /**
   * Display list of members that belong to the group.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   *
   * @return array
   *   The members overview View.
   */
  public function membersList(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_og_entity_type_id');

    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $route_match->getParameter($parameter_name);

    $arguments = [$group->getEntityTypeId(), $group->id()];
    return Views::getView('og_members_overview')->executeDisplay('default', $arguments);
  }

}
