<?php

declare(strict_types = 1);

namespace Drupal\og\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgMembershipTypeInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the members administration page.
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
    $group_type_id = $route_match->getRouteObject()->getOption('_og_entity_type_id');
    $group = $route_match->getParameter($group_type_id);
    $arguments = [$group->getEntityTypeId(), $group->id()];
    $build = [];
    $view = Views::getView('og_members_overview');
    if ($view) {
      $build = $view->executeDisplay('default', $arguments);
    }
    return $build;
  }

  /**
   * Displays add membership links for available membership types.
   *
   * Returns default membership type if that's all that exists.
   *
   * @return array
   *   A render array for a list of the og membership types that can be added;
   *   however, if there is only one og membership type defined for the site,
   *   the function will return the default add member form.
   */
  public function addPage(RouteMatchInterface $route_match) {
    $entity_type_id = $route_match->getRouteObject()
      ->getOption('_og_entity_type_id');

    $group = $route_match->getParameter($entity_type_id);

    $membership_types = $this->entityTypeManager
      ->getStorage('og_membership_type')
      ->loadMultiple();

    if ($membership_types && count($membership_types) == 1) {
      return $this->addForm($group, $membership_types[OgMembershipInterface::TYPE_DEFAULT]);
    }

    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
    ];

    $build['#cache']['tags'] = $this->entityTypeManager
      ->getDefinition('og_membership_type')
      ->getListCacheTags();

    $add_link_params = [
      'group' => $group->id(),
      'entity_type_id' => $group->getEntityType()->id(),
    ];

    foreach ($membership_types as $membership_type_id => $og_membership_type) {
      $add_link_params['og_membership_type'] = $membership_type_id;
      $build['#bundles'][$membership_type_id] = [
        'label' => $og_membership_type->label(),
        'description' => NULL,
        'add_link' => Link::createFromRoute($og_membership_type->label(), 'entity.og_membership.add_form', $add_link_params),
      ];
    }

    return $build;
  }

  /**
   * Provides the add member submission form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\og\OgMembershipTypeInterface $og_membership_type
   *   The membership type entity.
   *
   * @return array
   *   The member add form.
   */
  public function addForm(EntityInterface $group, OgMembershipTypeInterface $og_membership_type) {
    /** @var \Drupal\og\Entity\OgMembership $og_membership */
    $og_membership = OgMembership::create([
      'type' => $og_membership_type->id(),
      'entity_type' => $group->getEntityType()->id(),
      'entity_bundle' => $group->bundle(),
      'entity_id' => $group->id(),
    ]);

    return $this->entityFormBuilder()->getForm($og_membership, 'add');
  }

}
