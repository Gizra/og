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
