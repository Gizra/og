<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\og\OgResolvedGroupCollection;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;

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
   * @covers ::getGroupInfo
   *
   * @dataProvider getGroupInfoProvider
   *
   * @see testAddGroup()
   */
  public function testGetGroupInfo($votes, $expected_group_count) {
    $collection = new OgResolvedGroupCollection();

    foreach ($votes as $vote) {
      $collection->addGroup($this->groups[$vote['group']], $vote['cache_contexts'], $vote['weight']);
    }

    $info = $collection->getGroupInfo();

    // Check that the expected number of groups have been added.
    $this->assertEquals($expected_group_count, count($info));
  }

  /**
   * @covers ::removeGroup
   *
   * @dataProvider resolvedGroupsProvider
   */
  public function testRemoveGroup() {
    $this->markTestIncomplete();
  }

  /**
   * @covers ::hasGroup
   *
   * @dataProvider resolvedGroupsProvider
   */
  public function testHasGroup() {
    $this->markTestIncomplete();
  }

  /**
   * @covers ::getVoteWeight
   *
   * @dataProvider resolvedGroupsProvider
   */
  public function testGetVoteWeight() {
    $this->markTestIncomplete();
  }

  /**
   * @covers ::setVoteWeight
   *
   * @dataProvider resolvedGroupsProvider
   */
  public function testSetVoteWeight() {
    $this->markTestIncomplete();
  }

  /**
   * @covers ::sort
   *
   * @dataProvider resolvedGroupsProvider
   */
  public function testSort() {
    $this->markTestIncomplete();
  }

  /**
   * Provides data for testing methods on an OgResolvedGroupCollection object.
   */
  public function resolvedGroupsProvider() {
    return [

    ];
  }

}
