<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;

/**
 * Tests the RouteGroupResolver plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver
 */
class RouteGroupResolverTest extends OgRouteGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = RouteGroupResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'route_group';

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
  protected function getInjectedDependencies() {
    return [
      $this->routeMatch->reveal(),
      $this->groupTypeManager->reveal(),
      $this->entityTypeManager->reveal(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestEntityProperties() {
    return [
      'group' => ['type' => 'node', 'bundle' => 'group', 'group' => TRUE],
      'group_content' => ['type' => 'entity_test', 'bundle' => 'group_content'],
      'non_group' => ['type' => 'taxonomy_term', 'bundle' => 'taxonomy_term'],
    ];
  }

  /**
   * Returns a list of test entity types.
   *
   * This mimicks the data returned by EntityTypeManager::getDefinitions().
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   A list of mocked entity types.
   *
   * @see \Drupal\Core\Entity\EntityTypeManagerInterface::getDefinitions()
   */
  protected function getEntityTypes() {
    return [
      'node' => $this->prophesize(ContentEntityInterface::class),
    ];
  }

  /**
   * Data provider for testResolve().
   *
   * @see ::testResolve()
   */
  public function resolveProvider() {
    return [
      // Test that no groups are returned on a path that is not associated with
      // any entities.
      [
        // A path that is not associated with any entities.
        '/user/logout',
        // There is no entity on this route.
        NULL,
        // So the plugin should not find anything.
        [],
      ],
      // Test that if we are on the canonical entity page of a group, the
      // correct group is returned.
      [
        // We're on the canonical group entity page.
        '/node/{node}',
        // The test group is found on the route.
        'group',
        // The plugin should be able to figure out this is a group.
        ['group'],
      ],
      // Test that if we are on the delete form of a group, the correct group is
      // returned.
      [
        '/node/{node}/delete',
        'group',
        ['group'],
      ],
      // Test that if we are on the canonical entity page of a group content
      // entity, no group should be returned.
      [
        '/entity_test/{entity_test}',
        'group_content',
        [],
      ],
      // Test that if we are on the delete form of a group content entity, no
      // group should be returned.
      [
        '/entity_test/delete/entity_test/{entity_test}',
        'group_content',
        [],
      ],
      // Test that if we are on the canonical entity page of an entity that is
      // neither a group nor group content, no group should be returned.
      [
        '/taxonomy/term/{taxonomy_term}',
        'non_group',
        [],
      ],
      // Test that if we are on the delete form of an entity that is neither a
      // group nor group content, no group should be returned.
      [
        '/taxonomy/term/{taxonomy_term}/delete',
        'non_group',
        [],
      ],
    ];
  }

}
