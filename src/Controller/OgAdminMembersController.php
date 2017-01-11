<?php

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\Entity\OgMembership;
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

  /**
   * Provides the add member submission form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   *
   * @return array
   *   The member add form.
   */
  public function addMember(RouteMatchInterface $route_match) {
    /** @var \Drupal\og\Entity\OgMembershipType $membership_type */
    $membership_type = $route_match->getParameter('membership_type');
    $group_type_id = $route_match->getRouteObject()->getOption('_og_entity_type_id');

    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $route_match->getParameter($group_type_id);

    /** @var \Drupal\og\Entity\OgMembership $og_membership */
    $og_membership = OgMembership::create([
      'type' => $membership_type->id(),
      'entity_type' => $group->getEntityType()->id(),
      'entity_id' => $group->id(),
    ]);

    return $this->entityFormBuilder()->getForm($og_membership, 'add');
  }

}
