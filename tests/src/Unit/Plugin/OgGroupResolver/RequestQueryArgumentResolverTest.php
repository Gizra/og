<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og\Plugin\OgGroupResolver\RequestQueryArgumentResolver;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the RequestQueryArgumentResolver plugin.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\OgGroupResolver\RequestQueryArgumentResolver
 */
class RequestQueryArgumentResolverTest extends OgGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $className = RequestQueryArgumentResolver::class;

  /**
   * {@inheritdoc}
   */
  protected $pluginId = 'request_query_argument';

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Instantiate mocks of the classes that the plugins rely on.
    $this->requestStack = $this->prophesize(RequestStack::class);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $group_type
   *   The group type that is passed as a query argument.
   * @param string $group_id
   *   The group ID that is passed as a query argument.
   * @param array $previously_added_groups
   *   An array of test entity IDs that were added to the collection by plugins
   *   that ran previously.
   * @param string $expected_added_group
   *   The group that is expected to be added by the plugin. If left empty it is
   *   explicitly expected that the plugin will not add any group to the
   *   collection.
   *
   * @covers ::resolve
   * @dataProvider resolveProvider
   */
  public function testResolve($group_type = NULL, $group_id = NULL, array $previously_added_groups = [], $expected_added_group = NULL) {
    // It is expected that the plugin will retrieve the current request from the
    // request stack.
    $request = $this->prophesize(Request::class)->reveal();
    $this->requestStack->getCurrentRequest()
      ->willReturn($request)
      ->shouldBeCalled();

    // It will retrieve the query object from the request.
    /** @var \Symfony\Component\HttpFoundation\ParameterBag|\Prophecy\Prophecy\ObjectProphecy $query */
    $query = $this->prophesize(ParameterBag::class);

    // Mock methods to check for the existence and value of the query arguments
    // for the group entity type and ID. The plugin is allowed to call these.
    $query->has(RequestQueryArgumentResolver::GROUP_ID_ARGUMENT)->willReturn(!empty($group_id));
    $query->has(RequestQueryArgumentResolver::GROUP_TYPE_ARGUMENT)->willReturn(!empty($group_type));
    $query->get(RequestQueryArgumentResolver::GROUP_ID_ARGUMENT)->willReturn($group_id);
    $query->get(RequestQueryArgumentResolver::GROUP_TYPE_ARGUMENT)->willReturn($group_type);

    $request->query = $query->reveal();

    // The plugin may try to load the entity that is described in the query
    // arguments.
    if (!empty($group_type) && !empty($group_id)) {
      // The plugin is allowed to request the entity storage for the group.
      $storage = $this->prophesize(EntityStorageInterface::class);

      // The entity may be loaded from storage so the plugin can check whether
      // it is a group entity. This should only happen if it is a valid entity.
      if (!empty($this->testEntities[$group_id])) {
        $group = $this->testEntities[$group_id];
        if ($group->id() === $group_id && $group->getEntityTypeId() === $group_type) {
          $storage->load($group_id)->willReturn($group);
        }
      }

      $this->entityTypeManager->getStorage($group_type)->willReturn($storage);
    }

    // Construct a collection of groups that were discovered by other plugins.
    /** @var \Drupal\og\OgResolvedGroupCollectionInterface|\Prophecy\Prophecy\ObjectProphecy $collection */
    $collection = $this->prophesize(OgResolvedGroupCollectionInterface::class);

    // Set expectations for investigations the plugin may launch into the nature
    // of our test entities.
    foreach ($this->getTestEntityProperties() as $test_entity_id => $properties) {
      // The plugin may request if any of the entities are already discovered by
      // a previous plugin.
      $collection->hasGroup($this->testEntities[$test_entity_id])
        ->willReturn(in_array($test_entity_id, $previously_added_groups));

      // The plugin may ask whether this entity is a group.
      $this->groupTypeManager->isGroup($properties['type'], $properties['bundle'])
        ->willReturn(!empty($properties['group']));
    }

    // Add an expectation if the plugin should add a vote for a group or not,
    // depending on the provided test data.
    if ($expected_added_group) {
      $collection->addGroup($this->testEntities[$expected_added_group], ['url'])
        ->shouldBeCalled();
    }
    else {
      $collection->addGroup()
        ->shouldNotBeCalled();
    }

    // Launch the test. Any unmet expectation will cause a failure.
    $plugin = $this->getPluginInstance();
    $plugin->resolve($collection->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function getInjectedDependencies() {
    return [
      $this->requestStack->reveal(),
      $this->groupTypeManager->reveal(),
      $this->entityTypeManager->reveal(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTestEntityProperties() {
    return [
      // A test group.
      'group-0' => [
        'type' => 'node',
        'bundle' => 'group',
        'group' => TRUE,
      ],
      // Another test group.
      'group-1' => [
        'type' => 'taxonomy_term',
        'bundle' => 'assembly',
        'group' => TRUE,
      ],
      // A group content entity.
      'group_content' => [
        'type' => 'entity_test',
        'bundle' => 'content',
        'group_content' => ['group-0'],
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
      // Test that no group is added on a path that does not have a query
      // argument.
      [
        // There is no query argument for the entity ID.
        NULL,
        // There is no query argument for the bundle ID.
        NULL,
        // A group was added by another plugin.
        ['group-0'],
        // But this plugin should not add any group since there is no query
        // argument.
        NULL,
      ],
      // Test that no group is added on a path that has an invalid entity type
      // in the query arguments.
      [
        'parmigiano-reggiano',
        'group-0',
        ['group-0'],
        NULL,
      ],
      // Test that no groups are added on a path that has a missing entity type.
      [
        NULL,
        'group-0',
        ['group-0'],
        NULL,
      ],
      // Test that no groups are added on a path that has an invalid entity ID.
      [
        'node',
        // Group 1 is a taxonomy term, this ID is invalid for groups of type
        // node.
        'group-1',
        ['group-1'],
        NULL,
      ],
      // Test that no groups are added on a path that has a missing entity ID.
      [
        'node',
        NULL,
        ['group-0'],
        NULL,
      ],
      // Test that a vote can be added for a group that is present on the query
      // argument and has been previously added by another plugin.
      [
        'node',
        'group-0',
        ['group-0'],
        'group-0',
      ],
      // Test that a vote can be added for a group of a different entity type
      // that is present on the query argument and has been previously added by
      // another plugin.
      [
        'taxonomy_term',
        'group-1',
        ['group-1'],
        'group-1',
      ],
      // Test that a vote can be added for a group that is present on the query
      // argument and is part of multiple groups that have been added by other
      // plugins.
      [
        'node',
        'group-0',
        ['group-0', 'group-1'],
        'group-0',
      ],
      // Test that a vote can not be added for a group that is present on the
      // query argument but has not been previously added by another plugin. We
      // do not want users to be able to fake a group context by messing with
      // the query arguments.
      [
        'node',
        'group-0',
        ['group-1'],
        NULL,
      ],
      // Test that a vote can not be added for a group content entity that is
      // present on the query argument.
      [
        'entity_test',
        'group_content',
        ['group-0'],
        NULL,
      ],
      // Test that a vote can not be added for an entity that is present on the
      // query argument but is neither a group nor group content.
      [
        'entity_test',
        'non_group',
        ['group-0', 'group-1'],
        NULL,
      ],
    ];
  }

}
