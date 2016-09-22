<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\GroupTypeManager;
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
   * Data provider for testGetGroups().
   *
   * @see ::testGetGroups()
   */
  public function getGroupsProvider() {
    return [
      // Test that no groups are returned on a path that is not associated with
      // any entities.
      [
        // A path that is not associated with any entities.
        'user/logout',
      ],
    ];
  }

}
