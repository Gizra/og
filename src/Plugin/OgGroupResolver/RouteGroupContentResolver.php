<?php

namespace Drupal\og\Plugin\OgGroupResolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\GroupTypeManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\OgRouteGroupResolverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the group from the route.
 *
 * This plugin inspects the current route and checks if it is an entity path for
 * a group entity.
 *
 * @OgGroupResolver(
 *   id = "route_group_content",
 *   label = "Groups from the group content entity on the current route",
 *   description = @Translation("Checks if the current route is an entity path for a group content entity and returns the group(s) that it belongs to.")
 * )
 */
class RouteGroupContentResolver extends OgRouteGroupResolverBase {

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * Constructs a RouteGroupContentResolver.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   * @param \Drupal\og\GroupTypeManagerInterface $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   * @param \Drupal\og\OgGroupAudienceHelperInterface $group_audience_helper
   *   The OG group audience helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, GroupTypeManagerInterface $group_type_manager, EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, OgGroupAudienceHelperInterface $group_audience_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match, $group_type_manager, $entity_type_manager);
    $this->membershipManager = $membership_manager;
    $this->groupAudienceHelper = $group_audience_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('og.group_type_manager'),
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('og.group_audience_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    $entity = $this->getContentEntity();
    // Check if the route entity is group content by checking if it has a group
    // audience field.
    if ($entity && $this->groupAudienceHelper->hasGroupAudienceField($entity->getEntityTypeId(), $entity->bundle())) {
      $groups = $this->membershipManager->getGroups($entity);
      // The groups are returned as a two-dimensional array. Flatten it.
      $groups = array_reduce($groups, 'array_merge', []);

      foreach ($groups as $group) {
        $collection->addGroup($group, ['route']);
      }
    }
  }

}
