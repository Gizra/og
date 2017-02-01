<?php

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Displays add membership links for available membership types.
   *
   * Returns default membership type if that's all that exists.
   *
   * @return array
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return the default add member form.
   */
  public function addPage(RouteMatchInterface $route_match) {
    $bundles = $this->entityTypeManager()->getStorage('og_membership_type')->loadMultiple();
    if ($bundles && count($bundles) == 1) {
      $type = reset($types);
      return $this->addForm($route_match);
    }

    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
    ];

    $bundle_entity_type = $this->entityTypeManager()->getDefinition('og_membership_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    $group_type_id = $route_match->getRouteObject()->getOption('_og_entity_type_id');

    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $route_match->getParameter($group_type_id);
    $entity_type_id = $group->getEntityType()->id();

    foreach ($bundles as $bundle_name => $bundle_info) {
      $build['#bundles'][$bundle_name] = [
        'label' => $bundle_info['name'],
        'add_link' => Link::createFromRoute(
          $bundle_info['name'],
          "entity.$entity_type_id.og_admin_routes.add_membership",
          [$entity_type_id => $group->id(), 'membership_type' => $bundle_name]
        ),
      ];
    }

    return $build;
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
  public function addForm(RouteMatchInterface $route_match) {
    /** @var \Drupal\og\Entity\OgMembershipType $membership_type */
    $membership_type = $route_match->getParameter('membership_type');
    if ($membership_type === NULL) {
      $membership_type = $this->entityTypeManager->getStorage('og_membership_type')->load('default');
    }

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
