<?php

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OgAdminMembersController class.
 */
class OgAdminMembersController extends ControllerBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

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
    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $this->getGroupFromRoute($route_match);

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
    $membership_type_list = $this->entityTypeManager()->getStorage('og_membership_type')->loadMultiple();
    if ($membership_type_list && count($membership_type_list) == 1) {
      return $this->addForm($route_match);
    }

    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
    ];

    $bundle_entity_type = $this->entityTypeManager()->getDefinition('og_membership_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    $group = $this->getGroupFromRoute($route_match);

    $add_link_params = [
      'group' => $group->id(),
      'entity_type_id' => $group->getEntityType()->id(),
    ];

    foreach ($membership_type_list as $membership_type_id => $og_membership_type) {
      $add_link_params['membership_type'] = $membership_type_id;
      $build['#bundles'][$membership_type_id] = [
        'label' => $og_membership_type->label(),
        'add_link' => Link::createFromRoute($og_membership_type->label(), "entity.og_membership.add_form", $add_link_params),
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
    if (is_object($membership_type)) {
      $membership_type = $membership_type->id();
    }
    if ($membership_type === NULL) {
      $membership_type = 'default';
    }

    $group = $this->getGroupFromRoute($route_match);

    /** @var \Drupal\og\Entity\OgMembership $og_membership */
    $og_membership = OgMembership::create([
      'type' => $membership_type,
      'entity_type' => $group->getEntityType()->id(),
      'entity_id' => $group->id(),
    ]);

    return $this->entityFormBuilder()->getForm($og_membership, 'add');
  }

  /**
   * Determines the group associated with a route using this controller.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The group entity associated with the route.
   */
  protected function getGroupFromRoute(RouteMatchInterface $route_match) {
    $group = NULL;

    if ($group_type_id = $route_match->getRouteObject()->getOption('_og_entity_type_id')) {
      $group = $route_match->getParameter($group_type_id);
    }

    $entity_type = $route_match->getParameter('entity_type_id');
    $group_id = $route_match->getParameter('group');
    if ($entity_type && $group_id) {
      $group = $this->entityTypeManager->getStorage($entity_type)->load($group_id);
    }

    if ($group instanceof ContentEntityInterface === FALSE) {
      throw new \Exception('No group context was not found in the route match.');
    }

    if (!Og::isGroup($group->getEntityTypeId(), $group->bundle())) {
      throw new \Exception('The entity context found in the route match is not a valid group.');
    }

    return $group;
  }

}
