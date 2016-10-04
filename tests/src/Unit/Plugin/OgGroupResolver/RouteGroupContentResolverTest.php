<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\og\MembershipManagerInterface;
use Drupal\og\Plugin\OgGroupResolver\RouteGroupContentResolver;

/**
 * Tests the RouteGroupContentResolver plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\OgGroupResolver\RouteGroupContentResolver
 */
class RouteGroupContentResolverTest extends OgRouteGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = RouteGroupContentResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'route_group_content';

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $path
   *   The current route path that should be returned by the route matcher.
   * @param string $route_object_id
   *   The ID of the entity that is present on the current route, or NULL if we
   *   are not on a content entity path. The ID may be any of the ones created
   *   in the test setup and is stored in $this->testEntities.
   *
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve($path = NULL, $route_object_id = NULL, $expected_added_groups = [], $expected_removed_groups = []) {
    if ($path) {
      // It is expected that the plugin will retrieve the current path from the
      // route matcher.
      $this->willRetrieveCurrentPathFromRouteMatcher($path);
      // It is expected that the plugin will retrieve the full list of content
      // entity paths, so it can check whether the current path is related to a
      // content entity.
      $this->willRetrieveContentEntityPaths();
    }

    if ($route_object_id) {
      // The plugin might retrieve the route object. This should only happen if
      // we are on an actual entity path.
      $this->mightRetrieveRouteObject($route_object_id);
      // If a route object is returned the plugin will need to inspect it to
      // check if it is a group.
      $this->mightCheckIfRouteObjectIsGroup($route_object_id);
    }

    parent::testResolve($path, $route_object_id, $expected_added_groups, $expected_removed_groups);
  }

  /**
   * {@inheritdoc}
   */
  protected function getInjectedDependencies($path = NULL, $route_object_id = NULL) {
    return [
      $this->routeMatch->reveal(),
      $this->groupTypeManager->reveal(),
      $this->entityTypeManager->reveal(),
      $this->membershipManager->reveal()
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestEntityProperties() {
    return [
      // A 'normal' test group.
      'group-0' => [
        'type' => 'node',
        'bundle' => 'group',
        'group' => TRUE,
      ],
      // A test group that is also group content for group-0.
      'group-1' => [
        'type' => 'taxonomy_term',
        'bundle' => 'assembly',
        'group' => TRUE,
        'group_content' => ['group-0'],
      ],
      // Group content belonging to group-0.
      'group_content-0' => [
        'type' => 'entity_test',
        'bundle' => 'content',
        'group_content' => ['group-0'],
      ],
      // Group content belonging to group-1.
      'group_content-1' => [
        'type' => 'node',
        'bundle' => 'article',
        'group_content' => ['group-1'],
      ],
      // Group content belonging to both groups.
      'group_content-2' => [
        'type' => 'taxonomy_term',
        'bundle' => 'tags',
        'group_content' => ['group-0', 'group-1'],
      ],
      // An entity that is not a group nor group content.
      'non_group' => ['type' => 'entity_test', 'bundle' => 'non_group'],
    ];
  }

  /**
   * Data provider for testResolve().
   *
   * @see ::testResolve()
   */
  public function resolveProvider() {
    return [
      // Test that no groups are found on a path that is not associated with any
      // entities.
      [
        // A path that is not associated with any entities.
        '/user/logout',
        // There is no entity on this route.
        NULL,
        // So the plugin should not find anything.
        [],
      ],
      // Test that if we are on the canonical entity page of a group, no group
      // should be found.
      [
        // We're on the canonical group entity page.
        '/node/{node}',
        // The test group is found on the route.
        'group-0',
        // This is not a group content entity, so the plugin should not find any
        // results.
        [],
      ],
      // Test that if we are on the delete form of a group, no group is found.
      [
        '/node/{node}/delete',
        'group-0',
        [],
      ],
      // Test that if we are on the edit form of an entity that is both a group
      // and group content, the group is found of which this entity is group
      // content.
      [
        '/taxonomy/term/{taxonomy_term}/edit',
        'group-1',
        ['group-0'],
      ],
      // Test that if we are on the canonical entity page of a group content
      // entity that is linked to one group, the group is found.
      [
        '/entity_test/{entity_test}',
        'group_content-0',
        ['group-0'],
      ],
      // Test that if we are on the delete form of a group content entity, the
      // group that this group content belongs to is found.
      [
        '/node/{node}/delete',
        'group_content-1',
        ['group-1'],
      ],
      // Test that if we are on the canonical entity page of an entity that is
      // group content belonging to two groups, both are found.
      [
        '/taxonomy/term/{taxonomy_term}',
        'group_content-2',
        ['group-0', 'group-1'],
      ],
      // Test that if we are on the canonical entity page of an entity that is
      // neither a group nor group content, no group should be found.
      [
        '/entity_test/{entity_test}',
        'non_group',
        [],
      ],
      // Test that if we are on the delete form of an entity that is neither a
      // group nor group content, no group should be returned.
      [
        '/entity_test/delete/entity_test/{entity_test}',
        'non_group',
        [],
      ],
    ];
  }

}