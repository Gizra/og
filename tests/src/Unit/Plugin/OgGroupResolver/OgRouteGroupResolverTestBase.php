<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgResolvedGroupCollectionInterface;

/**
 * Base class for testing OgGroupResolver plugins that depend on the route.
 */
abstract class OgRouteGroupResolverTestBase extends OgGroupResolverTestBase {

  /**
   * Mocked test entities.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $testEntities;

  /**
   * The mocked route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $routeMatch;

  /**
   * The mocked OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $groupTypeManager;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Instantiate mocks of the classes that the plugins rely on.
    $this->routeMatch = $this->prophesize(RouteMatchInterface::class);
    $this->groupTypeManager = $this->prophesize(GroupTypeManager::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Update documentation.
   *
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve($path = NULL, $route_object_id = NULL, array $expected_added_groups = [], array $expected_removed_groups = []) {
    // @todo To be more flexible, these expectations should probably be moved to
    //   a bunch of helper methods, so that individual tests can add them as
    //   required.
    // Add expectations for the groups that are added and removed by the plugin.
    $test_entities = $this->testEntities;
    foreach ([&$expected_added_groups, &$expected_removed_groups] as &$expected_groups) {
      // Replace the entity IDs from the data provider with actual test
      // entities.
      $expected_groups = array_map(function ($item) use ($test_entities) {
        return $test_entities[$item];
      }, $expected_groups);
    }

    // Add expectations for groups that should be added or removed.
    /** @var \Drupal\og\OgResolvedGroupCollectionInterface|\Prophecy\Prophecy\ObjectProphecy $collection */
    $collection = $this->prophesize(OgResolvedGroupCollectionInterface::class);

    foreach ($expected_added_groups as $expected_added_group) {
      $collection->addGroup($expected_added_group)->shouldBeCalled();
    }

    foreach ($expected_removed_groups as $expected_removed_group) {
      $collection->removeGroup($expected_removed_group)->shouldBeCalled();
    }

    // Set expectations for when NO groups should be added or removed.
    if (empty($expected_added_groups)) {
      $collection->addGroup()->shouldNotBeCalled();
    }
    if (empty($expected_removed_groups)) {
      $collection->removeGroup()->shouldNotBeCalled();
    }

    // Launch the test. Any unmet expectation will cause a failure.
    $plugin = $this->getPluginInstance($this->getInjectedDependencies());
    $plugin->resolve($collection->reveal());
  }

  /**
   * {@inheritdoc}
   */
  public function testGetCacheContextIds() {
    $plugin = $this->getPluginInstance();
    $this->assertEquals(['route'], $plugin->getCacheContextIds());
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginInstance(array $args = []) {
    $args = $args ?: $this->getInjectedDependencies();
    return parent::getPluginInstance($args);
  }

  /**
   * Returns the mocked classes that the plugin depends on.
   *
   * @return array
   *   The mocked dependencies.
   */
  protected function getInjectedDependencies() {
    return [];
  }

}
