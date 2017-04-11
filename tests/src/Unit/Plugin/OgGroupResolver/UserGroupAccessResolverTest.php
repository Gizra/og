<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\Plugin\OgGroupResolver\UserGroupAccessResolver;

/**
 * Tests the UserGroupAccessResolver plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\OgGroupResolver\UserGroupAccessResolver
 */
class UserGroupAccessResolverTest extends OgGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = UserGroupAccessResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'user_access';

  /**
   * {@inheritdoc}
   *
   * @param array $previously_added_groups
   *   An array of test entity IDs that were added to the collection by plugins
   *   that ran previously.
   * @param array $expected_added_groups
   *   An array of groups that are expected to be added by the plugin. If left
   *   empty it is expected that the plugin will not add any group to the
   *   collection.
   * @param array $expected_removed_groups
   *   An array of groups that are expected to be removed by the plugin. If left
   *   empty it is expected that the plugin will not remove any group from the
   *   collection.
   *
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve(array $previously_added_groups = [], array $expected_added_groups = [], array $expected_removed_groups = []) {
    // Construct a collection of groups that were discovered by other plugins.
    /** @var \Drupal\og\OgResolvedGroupCollectionInterface|\Prophecy\Prophecy\ObjectProphecy $collection */
    $collection = $this->prophesize(OgResolvedGroupCollectionInterface::class);

    // It is expected that the plugin will retrieve the full set of information
    // about the groups in the collection.
    $test_entities = $this->testEntities;
    $group_info = array_map(function ($group) use ($test_entities) {
      return ['entity' => $test_entities[$group]];
    }, $previously_added_groups);
    $collection->getGroupInfo()
      ->willReturn($group_info)
      ->shouldBeCalled();

    // Add expectations for groups that should be added or removed.
    foreach ($expected_added_groups as $expected_added_group) {
      $collection->addGroup($test_entities[$expected_added_group], ['user'])->shouldBeCalled();
    }

    foreach ($expected_removed_groups as $expected_removed_group) {
      $collection->removeGroup($test_entities[$expected_removed_group])->shouldBeCalled();
    }

    // Set expectations for when NO groups should be added or removed.
    if (empty($expected_added_groups)) {
      $collection->addGroup()->shouldNotBeCalled();
    }
    if (empty($expected_removed_groups)) {
      $collection->removeGroup()->shouldNotBeCalled();
    }

    // Launch the test. Any unmet expectation will cause a failure.
    $plugin = $this->getPluginInstance();
    $plugin->resolve($collection->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function createMockedEntity($id, array $properties) {
    $entity = parent::createMockedEntity($id, $properties);

    // Mock the expected result of an access check on the entity.
    $entity->access('view')->willReturn($properties['current_user_has_access']);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestEntityProperties() {
    return [
      // Some test groups to which the current user has access.
      'group-access-0' => [
        'type' => 'node',
        'bundle' => 'group',
        'group' => TRUE,
        'current_user_has_access' => TRUE,
      ],
      'group-access-1' => [
        'type' => 'taxonomy_term',
        'bundle' => 'assembly',
        'group' => TRUE,
        'current_user_has_access' => TRUE,
      ],
      // Some test groups to which the current user does not have access.
      'group-noaccess-0' => [
        'type' => 'entity_test',
        'bundle' => 'cluster',
        'group' => TRUE,
        'current_user_has_access' => FALSE,
      ],
      'group-noaccess-1' => [
        'type' => 'block_content',
        'bundle' => 'flock',
        'group' => TRUE,
        'current_user_has_access' => FALSE,
      ],
    ];
  }

  /**
   * Data provider for testResolve().
   *
   * @see ::testResolve()
   */
  public function resolveProvider() {
    return [
      // Test that the groups to which the user does not have access are removed
      // from a collection that has both accessible and non-accessible groups.
      // The accessible groups should get a vote added, so that the 'user' cache
      // context is correctly set on it.
      [
        // We start with a collection that has a mix of accessible and non-
        // accessible groups.
        [
          'group-access-0',
          'group-access-1',
          'group-noaccess-0',
          'group-noaccess-1',
        ],
        // A vote should be added to the accessible groups.
        ['group-access-0', 'group-access-1'],
        // The non-accessible groups should be removed.
        ['group-noaccess-0', 'group-noaccess-1'],
      ],
      // Test that no groups are removed when the collection does not contain
      // any non-accessible groups.
      [
        ['group-access-0', 'group-access-1'],
        ['group-access-0', 'group-access-1'],
        [],
      ],
      // Test that no votes are added when the collection does not contain any
      // accessible groups. The non-accessible ones should be removed.
      [
        ['group-noaccess-0', 'group-noaccess-1'],
        [],
        ['group-noaccess-0', 'group-noaccess-1'],
      ],
      // Test that nothing happens on an empty collection.
      [
        [],
        [],
        [],
      ],
    ];
  }

}
