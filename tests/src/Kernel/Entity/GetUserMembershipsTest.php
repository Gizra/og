<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Tests retrieving OgMembership entities associated with a given user.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class GetUserMembershipsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

  /**
   * Test groups.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $groups = [];
  
  /**
   * Test users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    // Create two groups.
    for ($i = 0; $i < 2; $i++) {
      $bundle = "node_$i";
      NodeType::create([
        'name' => $this->randomString(),
        'type' => $bundle,
      ])->save();
      Og::groupManager()->addGroup('node', $bundle);

      $group = Node::create([
        'title' => $this->randomString(),
        'type' => $bundle,
      ]);
      $group->save();
      $this->groups[] = $group;
    }

    // Create test users with different membership statuses in the two groups.
    $matrix = [
      // A user which is an active member of the first group.
      [OgMembershipInterface::STATE_ACTIVE, NULL],

      // A user which is a pending member of the second group.
      [NULL, OgMembershipInterface::STATE_PENDING],

      // A user which is an active member of both groups.
      [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_ACTIVE],

      // A user which is a pending member of the first group and blocked in the
      // second group.
      [OgMembershipInterface::STATE_PENDING, OgMembershipInterface::STATE_BLOCKED],

      // A user which is not subscribed to either of the two groups.
      [NULL, NULL],
    ];

    foreach ($matrix as $user_key => $statuses) {
      $user = User::create(['name' => $this->randomString()]);
      $user->save();
      $this->users[$user_key] = $user;
      foreach ($statuses as $group_key => $status) {
        $group = $this->groups[$group_key];
        if ($status) {
          $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
          $membership
            ->setUser($user->id())
            ->setEntityId($group->id())
            ->setEntityType($group->getEntityTypeId())
            ->setState($status)
            ->save();
        }
      }
    }
  }

  /**
   * Tests retrieval of OG Membership entities associated with a given user.
   *
   * @param int $user
   *   The key of the user in the $this->users array.
   * @param array $states
   *   Array with the states to retrieve.
   * @param string $field_name
   *   The field name associated with the group.
   * @param array $expected
   *   An array containing the expected results to be returned.
   *
   * @covers ::getUserMemberships
   * @dataProvider membershipDataProvider
   */
  public function testGetUserMemberships($user, array $states, $field_name, array $expected) {
    $result = Og::getUserMemberships($this->users[$user], $states, $field_name);

    // Check that the correct number of results is returned.
    $this->assertEquals(count($expected), count($result));
    
    // Inspect the results that were returned.
    foreach ($result as $key => $membership) {
      // Check that all result items are OgMembership objects.
      // @todo Check for \Drupal\og\OgMembershipInterface once OgMembership
      //   implements it.
      $this->assertInstanceOf('Drupal\og\Entity\OgMembership', $membership);
      // Check that the results are keyed by OgMembership ID.
      $this->assertEquals($membership->id(), $key);
    }

    // Check that all expected results are returned.
    foreach ($expected as $expected_group) {
      $expected_id = $this->groups[$expected_group]->id();
      foreach ($result as $membership) {
        if ($membership->getEntityId() === $expected_id) {
          // Test successful: the expected result was found.
          continue 2;
        }
      }
      $this->fail("The expected group with ID $expected_id was not found.");
    }
  }
  
  /**
   * Provides test data to test retrieval of memberships.
   *
   * @return array
   *   An array of test properties. Each property is an indexed array with the
   *   following items:
   *   - An optional string indicating the group type ID to be returned.
   *   - An optional string indicating the group bundle to be returned.
   *   - An array containing the expected results to be returned.
   */
  public function membershipDataProvider() {
    return [
      // The first user is an active member of the first group.
      // Query default values. The group should be returned.
      [0, [], NULL, [0]],
      // Filter by active state.
      [0, [OgMembershipInterface::STATE_ACTIVE], NULL, [0]],
      // Filter by active + pending state.
      [0, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING], NULL, [0]],
      // Filter by blocked + pending state. Since the user is active this should
      // not return any matches.
      [0, [OgMembershipInterface::STATE_BLOCKED, OgMembershipInterface::STATE_PENDING], NULL, []],
      // Filter by a non-existing field name. This should not return any
      // matches.
      [0, [], 'non_existing_field_name', []],
      
      // The second user is a pending member of the second group.
      // Query default values. The group should be returned.
      [1, [], NULL, [1]],
      // Filter by pending state.
      [1, [OgMembershipInterface::STATE_PENDING], NULL, [1]],
      // Filter by active state. The user is pending so this should not return
      // any matches.
      [1, [OgMembershipInterface::STATE_ACTIVE], NULL, []],

      // The third user is an active member of both groups.
      // Query default values. Both groups should be returned.
      [2, [], NULL, [0, 1]],
      // Filter by active state.
      [2, [OgMembershipInterface::STATE_ACTIVE], NULL, [0, 1]],
      // Filter by blocked state. This should not return any matches.
      [2, [OgMembershipInterface::STATE_BLOCKED], NULL, []],

      // The fourth user is a pending member of the first group and blocked in
      // the second group.
      // Query default values. Both groups should be returned.
      [3, [], NULL, [0, 1]],
      // Filter by active state. No results should be returned.
      [3, [OgMembershipInterface::STATE_ACTIVE], NULL, []],
      // Filter by pending state.
      [3, [OgMembershipInterface::STATE_PENDING], NULL, [0]],
      // Filter by blocked state.
      [3, [OgMembershipInterface::STATE_BLOCKED], NULL, [1]],
      // Filter by combinations of states.
      [3, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING], NULL, [0]],
      [3, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING, OgMembershipInterface::STATE_BLOCKED], NULL, [0, 1]],
      [3, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_BLOCKED], NULL, [1]],
      [3, [OgMembershipInterface::STATE_PENDING, OgMembershipInterface::STATE_BLOCKED], NULL, [0, 1]],

      // A user which is not subscribed to either of the two groups.
      [4, [], NULL, []],
      [4, [OgMembershipInterface::STATE_ACTIVE], NULL, []],
      [4, [OgMembershipInterface::STATE_BLOCKED], NULL, []],
      [4, [OgMembershipInterface::STATE_PENDING], NULL, []],
      [4, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING, OgMembershipInterface::STATE_BLOCKED], NULL, []],
    ];
    return [
      [NULL, NULL, ['node' => [0, 1], 'entity_test' => [0, 1]]],
      ['node', NULL, ['node' => [0, 1]]],
      ['entity_test', NULL, ['entity_test' => [0, 1]]],
      ['node', 'node_0', ['node' => [0]]],
      ['entity_test', 'entity_test_1', ['entity_test' => [1]]],
    ];
  }
  
  /**
   * Tests retrieval of groups IDs that are associated with given group content.
   *
   * @param string $group_type_id
   *   Optional group type ID to be passed as an argument to the method under
   *   test.
   * @param string $group_bundle
   *   Optional group bundle to be passed as an argument to the method under
   *   test.
   * @param array $expected
   *   An array containing the expected results to be returned.
   *
   * @covers ::getGroupIds
   * @dataProvider groupContentProvider
   */
  public function _testGetGroupIds($group_type_id, $group_bundle, array $expected) {
    $result = Og::getGroupIds($this->groupContent, $group_type_id, $group_bundle);

    // Check that the correct number of results is returned.
    $this->assertEquals(count($expected, COUNT_RECURSIVE), count($result, COUNT_RECURSIVE));

    // Check that all expected results are returned.
    foreach ($expected as $expected_type => $expected_keys) {
      foreach ($expected_keys as $expected_key) {
        $this->assertTrue(in_array($this->groups[$expected_type][$expected_key]->id(), $result[$expected_type]));
        $this->fail("The expected group of type $expected_type and key $expected_key is found.");
      }
    }
  }

  /**
   * Tests retrieval of groups that are associated with a given group content.
   *
   * @param string $group_type_id
   *   Optional group type ID to be passed as an argument to the method under
   *   test.
   * @param string $group_bundle
   *   Optional group bundle to be passed as an argument to the method under
   *   test.
   * @param array $expected
   *   An array containing the expected results to be returned.
   *
   * @covers ::getGroups
   * @dataProvider groupContentProvider
   */
  public function _testGetGroups($group_type_id, $group_bundle, array $expected) {
    $result = Og::getGroups($this->groupContent, $group_type_id, $group_bundle);

    // Check that the correct number of results is returned.
    $this->assertEquals(count($expected, COUNT_RECURSIVE), count($result, COUNT_RECURSIVE));

    // Check that all expected results are returned.
    foreach ($expected as $expected_type => $expected_keys) {
      foreach ($expected_keys as $expected_key) {
        /** @var \Drupal\Core\Entity\EntityInterface $expected_group */
        $expected_group = $this->groups[$expected_type][$expected_key];
        /** @var \Drupal\Core\Entity\EntityInterface $group */
        foreach ($result[$expected_type] as $key => $group) {
          if ($group->getEntityTypeId() === $expected_group->getEntityTypeId() && $group->id() === $expected_group->id()) {
            // The expected result was found. Continue the test.
            continue 2;
          }
        }
        // The expected result was not found.
        $this->fail("The expected group of type $expected_type and key $expected_key is found.");
      }
    }
  }

  /**
   * Tests if the number of groups associated with group content is correct.
   *
   * @param string $group_type_id
   *   Optional group type ID to be passed as an argument to the method under
   *   test.
   * @param string $group_bundle
   *   Optional group bundle to be passed as an argument to the method under
   *   test.
   * @param array $expected
   *   An array containing the expected results to be returned.
   *
   * @covers ::getGroupCount
   * @dataProvider groupContentProvider
   */
  public function _testGetGroupCount($group_type_id, $group_bundle, array $expected) {
    $result = Og::getGroupCount($this->groupContent, $group_type_id, $group_bundle);

    // Check that the correct results is returned.
    $this->assertEquals(count($expected, COUNT_RECURSIVE) - count($expected), $result);
  }

  /**
   * Provides test data.
   *
   * @return array
   *   An array of test properties. Each property is an indexed array with the
   *   following items:
   *   - An optional string indicating the group type ID to be returned.
   *   - An optional string indicating the group bundle to be returned.
   *   - An array containing the expected results to be returned.
   */
  public function groupContentProvider() {
    return [
      [NULL, NULL, ['node' => [0, 1], 'entity_test' => [0, 1]]],
      ['node', NULL, ['node' => [0, 1]]],
      ['entity_test', NULL, ['entity_test' => [0, 1]]],
      ['node', 'node_0', ['node' => [0]]],
      ['entity_test', 'entity_test_1', ['entity_test' => [1]]],
    ];
  }

}
