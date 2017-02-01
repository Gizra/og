<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\og\OgResolvedGroupCollection;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the collecting of resolved groups to pass as a route context.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\OgResolvedGroupCollection
 */
class OgResolvedGroupCollectionTest extends UnitTestCase {

  /**
   * An array of mocked test groups, keyed by entity type ID and entity ID.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]|\Prophecy\Prophecy\ObjectProphecy[]
   */
  protected $groups;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Mock some test groups.
    foreach (['node', 'entity_test', 'taxonomy_term', 'block_content'] as $entity_type) {
      for ($i = 0; $i < 2; $i++) {
        $entity_id = "$entity_type-$i";
        /** @var \Drupal\Core\Entity\ContentEntityInterface|\Prophecy\Prophecy\ObjectProphecy $entity */
        $entity = $this->prophesize(ContentEntityInterface::class);
        $entity->getEntityTypeId()->willReturn($entity_type);
        $entity->id()->willReturn($entity_id);
        $this->groups[$entity_id] = $entity->reveal();
      }
    }
  }

  /**
   * Tests adding a group to the collection, or casting an additional vote.
   *
   * @covers ::addGroup
   */
  public function testAddGroup() {
    $collection = new OgResolvedGroupCollection();

    foreach ($this->groups as $group) {
      $key = $group->getEntityTypeId() . '|' . $group->id();

      // Initially the group should not exist in the collection.
      $this->assertFalse($collection->hasGroup($group));

      // Try adding the group without any optional parameters.
      $collection->addGroup($group);

      // The group should now exist in the collection.
      $this->assertTrue($collection->hasGroup($group));
      $info = $collection->getGroupInfo()[$key];
      $this->assertEquals($info['entity'], $group);

      // There should not be any cache contexts associated with it.
      $this->assertArrayNotHasKey('cache_contexts', $info);

      // There should be a single vote, which was cast with the default vote
      // weight (0).
      $this->assertEquals(1, count($info['votes']));
      $this->assertEquals(0, $info['votes'][0]);

      // Add a second vote for the group, this time passing cache contexts.
      $collection->addGroup($group, ['route', 'url']);

      // The cache contexts should now be associated with the group.
      $info = $collection->getGroupInfo()[$key];
      $this->assertEquals(['route', 'url'], array_values($info['cache_contexts']));

      // There should now be two votes, and both should have been cast with the
      // default vote weight.
      $this->assertEquals(2, count($info['votes']));
      $this->assertEquals(0, $info['votes'][0]);
      $this->assertEquals(0, $info['votes'][1]);

      // Add a third vote, this time specifying both a cache context and a
      // custom vote weight.
      $weight = rand(-100, 100);
      $collection->addGroup($group, ['user'], $weight);

      // The additional cache context should now be associated with the group.
      $info = $collection->getGroupInfo()[$key];
      $this->assertEquals(['route', 'url', 'user'], array_values($info['cache_contexts']));

      // There should now be three votes, the last of which having the custom
      // vote weight.
      $this->assertEquals(3, count($info['votes']));
      $this->assertEquals(0, $info['votes'][0]);
      $this->assertEquals(0, $info['votes'][1]);
      $this->assertEquals($weight, $info['votes'][2]);

      // Adding another vote using a cache context that has been set before
      // should not cause the cache context to be listed twice.
      $collection->addGroup($group, ['url', 'user']);
      $info = $collection->getGroupInfo()[$key];
      $this->assertEquals(['route', 'url', 'user'], array_values($info['cache_contexts']));
    }
  }

  /**
   * Tests retrieving group info from the collection.
   *
   * This simply tests that the group info is returned and contains the correct
   * number of results. The actual content of the group info is tested in
   * testAddGroup().
   *
   * @param array $votes
   *   An array of associative arrays representing voting information, with the
   *   following keys:
   *   - group: the ID of the group to add a vote for.
   *   - cache_contexts: an array of cache_contexts to associate with the group.
   *     To omit associating cache contexts, set to an empty array.
   *   - weight: an integer representing the vote weight. Set to NULL to omit.
   * @param array $expected_groups
   *   The groups that are expected to be present after all votes are added,
   *   ordered by ranking.
   *
   * @covers ::getGroupInfo
   *
   * @dataProvider groupVotesProvider
   *
   * @see testAddGroup()
   */
  public function testGetGroupInfo(array $votes, array $expected_groups) {
    $collection = new OgResolvedGroupCollection();

    foreach ($votes as $vote) {
      $collection->addGroup($this->groups[$vote['group']], $vote['cache_contexts'], $vote['weight']);
    }

    $info = $collection->getGroupInfo();

    // Check that the expected number of groups have been added.
    $this->assertEquals(count($expected_groups), count($info));
  }

  /**
   * Tests removing groups from the collection.
   *
   * @covers ::removeGroup
   */
  public function testRemoveGroup() {
    $collection = new OgResolvedGroupCollection();

    // Add some random votes for a random selection of groups.
    $groups = [];
    for ($i = 0; $i < 20; $i++) {
      // Pick a random group.
      $group = $this->groups[array_rand($this->groups)];

      // Add a vote for the group.
      $collection->addGroup($group);

      // Keep track of the groups we've added.
      $key = $group->getEntityTypeId() . '|' . $group->id();
      $groups[$key] = $group;
    }

    // Check that all our groups were added correctly.
    $this->assertEquals(count($groups), count($collection->getGroupInfo()));

    // Loop over the added groups and delete them one by one.
    foreach ($groups as $group) {
      // Initially the group should be there.
      $this->assertTrue($collection->hasGroup($group));

      // When we remove it the group should no longer be there.
      $collection->removeGroup($group);
      $this->assertFalse($collection->hasGroup($group));
    }

    // Check that all our groups were removed correctly.
    $this->assertEquals(0, count($collection->getGroupInfo()));
  }

  /**
   * Tests if it is possible to check if a group exists in the collection.
   *
   * @covers ::hasGroup
   */
  public function testHasGroup() {
    $collection = new OgResolvedGroupCollection();

    // Randomly select half of the test groups and add them.
    $random_selection = array_rand($this->groups, count($this->groups) / 2);
    foreach ($random_selection as $key) {
      $collection->addGroup($this->groups[$key]);
    }

    // Loop over all groups and check that ::hasGroup() returns the correct
    // result.
    foreach ($this->groups as $key => $group) {
      $this->assertEquals(in_array($key, $random_selection), $collection->hasGroup($group));
    }
  }

  /**
   * Tests if it is possible to retrieve and set the default vote weight.
   *
   * @covers ::getVoteWeight
   * @covers ::setVoteWeight
   */
  public function testGetSetVoteWeight() {
    $collection = new OgResolvedGroupCollection();

    // Test that the vote weight is initially 0.
    $this->assertEquals(0, $collection->getVoteWeight());

    // Test setting and getting a range of weights.
    for ($weight = -100; $weight <= 100; $weight++) {
      $collection->setVoteWeight($weight);
      $this->assertEquals($weight, $collection->getVoteWeight());
    }
  }

  /**
   * Tests if the groups can be correctly sorted according to the cast votes.
   *
   * @param array $votes
   *   An array of associative arrays representing voting information, with the
   *   following keys:
   *   - group: the ID of the group to add a vote for.
   *   - cache_contexts: an array of cache_contexts to associate with the group.
   *     To omit associating cache contexts, set to an empty array.
   *   - weight: an integer representing the vote weight. Set to NULL to omit.
   * @param array $expected_groups
   *   The groups that are expected to be present after all votes are added,
   *   ordered by ranking.
   *
   * @covers ::sort
   *
   * @dataProvider groupVotesProvider
   *
   * @see testAddGroup()
   */
  public function testSort(array $votes, array $expected_groups) {
    $collection = new OgResolvedGroupCollection();

    // Cast all votes.
    foreach ($votes as $vote) {
      $collection->addGroup($this->groups[$vote['group']], $vote['cache_contexts'], $vote['weight']);
    }

    // Check that the groups can be correctly sorted.
    $collection->sort();
    $info = $collection->getGroupInfo();

    $actual_groups = array_values(array_map(function ($group_info) {
      return $group_info['entity']->id();
    }, $info));

    $this->assertEquals($expected_groups, $actual_groups);
  }

  /**
   * Tests that only the right data type can be passed as vote weight.
   *
   * @param mixed $weight
   *   The value to pass as a vote weight.
   *
   * @covers ::addGroup
   * @covers ::setVoteWeight
   *
   * @dataProvider mixedDataProvider
   */
  public function testVoteWeightDataType($weight) {
    $collection = new OgResolvedGroupCollection();

    $group = $this->groups[array_rand($this->groups)];

    // It should be possible to pass NULL as a custom vote weight when adding a
    // new vote, but not to set it as the default vote weight.
    if (is_null($weight)) {
      try {
        $collection->addGroup($group, [], $weight);
      }
      catch (\InvalidArgumentException $e) {
        $this->fail('It is possible to pass NULL as the group weight when adding a vote.');
      }
      try {
        $collection->setVoteWeight($weight);
        $this->fail('It is not possible to set NULL as the default group weight.');
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }

      // The default vote weight should still be 0.
      $this->assertEquals(0, $collection->getVoteWeight());
    }

    // If the weight is an integer, it should be possible to set it as the
    // default vote weight, or to pass it as a custom vote weight.
    elseif (is_int($weight)) {
      $collection->addGroup($group, [], $weight);
      $collection->setVoteWeight($weight);

      // The default vote weight should be set to the value.
      $this->assertEquals($weight, $collection->getVoteWeight());
    }

    // If the weight is any value other than an integer, an exception should be
    // thrown.
    else {
      try {
        $collection->addGroup($group, [], $weight);
        $this->fail('Passing a non-integer value as the vote weight when adding a group throws an exception.');
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }
      try {
        $collection->setVoteWeight($weight);
        $this->fail('Setting a non-integer value as the vote weight throws an exception.');
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }

      // The default vote weight should still be 0.
      $this->assertEquals(0, $collection->getVoteWeight());
    }
  }

  /**
   * Provides data for testing the retrieval of group information.
   *
   * @return array
   *   An array of test data, each item an array with these two values:
   *   - An associative array representing voting information, with the
   *     following keys:
   *     - group: the ID of the group to add a vote for.
   *     - cache_contexts: an array of cache_contexts to associate with the
   *       group. To omit associating cache contexts, set to an empty array.
   *     - weight: an integer representing the vote weight. Set to NULL to omit.
   *   - An array containing the IDs of the groups that are expected to be
   *     present in the collection after all votes are added, in the order they
   *     are expected to be according to their votes.
   */
  public function groupVotesProvider() {
    return [
      // A simple vote for a group.
      [
        [
          [
            'group' => 'node-0',
            'cache_contexts' => [],
            'weight' => NULL,
          ],
        ],
        // There is one group.
        ['node-0'],
      ],

      // 3 votes for the same group.
      [
        [
          [
            'group' => 'entity_test-0',
            'cache_contexts' => [],
            'weight' => NULL,
          ],
          [
            'group' => 'entity_test-0',
            'cache_contexts' => ['user'],
            'weight' => 0,
          ],
          [
            'group' => 'entity_test-0',
            'cache_contexts' => ['route', 'user'],
            'weight' => -1,
          ],
        ],
        // There is one group.
        ['entity_test-0'],
      ],

      // A 'typical' test case with 5 votes for 3 different groups.
      [
        [
          [
            'group' => 'taxonomy_term-1',
            'cache_contexts' => [],
            'weight' => NULL,
          ],
          [
            'group' => 'block_content-0',
            'cache_contexts' => ['user'],
            'weight' => NULL,
          ],
          [
            'group' => 'node-1',
            'cache_contexts' => ['route'],
            'weight' => -1,
          ],
          [
            'group' => 'block_content-0',
            'cache_contexts' => ['route', 'user'],
            'weight' => -1,
          ],
          [
            'group' => 'taxonomy_term-1',
            'cache_contexts' => [],
            'weight' => -2,
          ],
        ],
        // The resulting groups in the collection, ordered by votes and weight.
        [
          // 2 votes, total weight -1.
          'block_content-0',
          // 2 votes, total weight -2.
          'taxonomy_term-1',
          // 1 vote.
          'node-1',
        ],
      ],

      // Groups with more votes should rank higher than groups with fewer votes,
      // regardless of the vote weight.
      [
        [
          [
            'group' => 'taxonomy_term-0',
            'cache_contexts' => ['route'],
            'weight' => 100,
          ],
          [
            'group' => 'block_content-0',
            'cache_contexts' => ['user'],
            'weight' => NULL,
          ],
          [
            'group' => 'block_content-1',
            'cache_contexts' => [],
            'weight' => 99999,
          ],
          [
            'group' => 'taxonomy_term-0',
            'cache_contexts' => [],
            'weight' => -300,
          ],
          [
            'group' => 'block_content-0',
            'cache_contexts' => ['route', 'user'],
            'weight' => NULL,
          ],
          [
            'group' => 'taxonomy_term-0',
            'cache_contexts' => [],
            'weight' => -3,
          ],
        ],
        [
          'taxonomy_term-0',
          'block_content-0',
          'block_content-1',
        ],
      ],

      // If multiple groups have the same number of votes, then the ones with
      // higher vote weights should be ranked higher.
      [
        [
          [
            'group' => 'entity_test-0',
            'cache_contexts' => ['user'],
            'weight' => 10,
          ],
          [
            'group' => 'block_content-0',
            'cache_contexts' => ['route', 'user'],
            'weight' => NULL,
          ],
          [
            'group' => 'node-1',
            'cache_contexts' => [],
            'weight' => 99999,
          ],
          [
            'group' => 'taxonomy_term-1',
            'cache_contexts' => ['url'],
            'weight' => 123,
          ],
          [
            'group' => 'taxonomy_term-1',
            'cache_contexts' => [],
            'weight' => 0,
          ],
          [
            'group' => 'entity_test-0',
            'cache_contexts' => [],
            'weight' => -3,
          ],
          [
            'group' => 'block_content-0',
            'cache_contexts' => ['route'],
            'weight' => -1,
          ],
          [
            'group' => 'node-1',
            'cache_contexts' => [],
            'weight' => -8,
          ],
        ],
        [
          'node-1',
          'taxonomy_term-1',
          'entity_test-0',
          'block_content-0',
        ],
      ],
    ];
  }

  /**
   * Provides mixed data for testing data types.
   */
  public function mixedDataProvider() {
    return [
      [NULL],
      [TRUE],
      [FALSE],
      [0],
      [1],
      [100],
      [-100],
      [1.00],
      [1.2e3],
      [7E-10],
      [100 / 3],
      [-100 / 3],
      [0x100],
      [0123],
      [0b00100011],
      ['<script>alert(123)</script>'],
      ['😍'],
      [['foo', 'bar']],
      [new \stdClass()],
      [$this],
    ];
  }

}
