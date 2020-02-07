<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Tests retrieving OgMembership entities associated with a given user.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class GetMembershipsTest extends KernelTestBase {

  use OgMembershipCreationTrait;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $this->installSchema('user', ['users_data']);

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create group admin user.
    $group_admin = User::create(['name' => $this->randomString()]);
    $group_admin->save();

    // Create two groups.
    for ($i = 0; $i < 2; $i++) {
      $bundle = "node_$i";
      NodeType::create([
        'name' => $this->randomString(),
        'type' => $bundle,
      ])->save();
      Og::groupTypeManager()->addGroup('node', $bundle);

      $group = Node::create([
        'title' => $this->randomString(),
        'type' => $bundle,
        'uid' => $group_admin->id(),
      ]);
      $group->save();
      $this->groups[] = $group;
    }

    // Create test users with different membership states in the two groups.
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

    foreach ($matrix as $user_key => $states) {
      $user = User::create(['name' => $this->randomString()]);
      $user->save();
      $this->users[$user_key] = $user;
      foreach ($states as $group_key => $state) {
        $group = $this->groups[$group_key];
        if ($state) {
          $this->createOgMembership($group, $user, NULL, $state);
        }
      }
    }
  }

  /**
   * Tests retrieval of OG Membership entities associated with a given user.
   *
   * @param int $index
   *   The array index in the $this->users array of the user to test.
   * @param array $states
   *   Array with the states to retrieve.
   * @param array $expected
   *   An array containing the expected results to be returned.
   *
   * @covers ::getMemberships
   * @dataProvider membershipDataProvider
   */
  public function testGetMemberships($index, array $states, array $expected) {
    $result = Og::getMemberships($this->users[$index], $states);

    // Check that the correct number of results is returned.
    $this->assertEquals(count($expected), count($result));

    // Inspect the results that were returned.
    foreach ($result as $key => $membership) {
      // Check that all result items are OgMembership objects.
      $this->assertInstanceOf('Drupal\og\OgMembershipInterface', $membership);
      // Check that the results are keyed by OgMembership ID.
      $this->assertEquals($membership->id(), $key);
    }

    // Check that all expected results are returned.
    foreach ($expected as $expected_group) {
      $expected_id = $this->groups[$expected_group]->id();
      foreach ($result as $membership) {
        if ($membership->getGroupId() === $expected_id) {
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
   *   - The key of the user in the $this->users array for which to retrieve
   *     memberships.
   *   - An array of membership states to filter on.
   *   - An array containing the expected results to be returned.
   */
  public function membershipDataProvider() {
    return [
      // The first user is an active member of the first group.
      // Query default values. The group should be returned.
      [0, [], [0]],
      // Filter by active state.
      [0, [OgMembershipInterface::STATE_ACTIVE], [0]],
      // Filter by active + pending state.
      [0, [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_PENDING,
      ], [0],
      ],
      // Filter by blocked + pending state. Since the user is active this should
      // not return any matches.
      [0, [
        OgMembershipInterface::STATE_BLOCKED,
        OgMembershipInterface::STATE_PENDING,
      ], [],
      ],

      // The second user is a pending member of the second group.
      // Query default values. The group should be returned.
      [1, [], [1]],
      // Filter by pending state.
      [1, [OgMembershipInterface::STATE_PENDING], [1]],
      // Filter by active state. The user is pending so this should not return
      // any matches.
      [1, [OgMembershipInterface::STATE_ACTIVE], []],

      // The third user is an active member of both groups.
      // Query default values. Both groups should be returned.
      [2, [], [0, 1]],
      // Filter by active state.
      [2, [OgMembershipInterface::STATE_ACTIVE], [0, 1]],
      // Filter by blocked state. This should not return any matches.
      [2, [OgMembershipInterface::STATE_BLOCKED], []],

      // The fourth user is a pending member of the first group and blocked in
      // the second group.
      // Query default values. Both groups should be returned.
      [3, [], [0, 1]],
      // Filter by active state. No results should be returned.
      [3, [OgMembershipInterface::STATE_ACTIVE], []],
      // Filter by pending state.
      [3, [OgMembershipInterface::STATE_PENDING], [0]],
      // Filter by blocked state.
      [3, [OgMembershipInterface::STATE_BLOCKED], [1]],
      // Filter by combinations of states.
      [3, [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_PENDING,
      ], [0],
      ],
      [3, [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_PENDING,
        OgMembershipInterface::STATE_BLOCKED,
      ], [0, 1],
      ],
      [3, [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_BLOCKED,
      ], [1],
      ],
      [3, [
        OgMembershipInterface::STATE_PENDING,
        OgMembershipInterface::STATE_BLOCKED,
      ], [0, 1],
      ],

      // A user which is not subscribed to either of the two groups.
      [4, [], []],
      [4, [OgMembershipInterface::STATE_ACTIVE], []],
      [4, [OgMembershipInterface::STATE_BLOCKED], []],
      [4, [OgMembershipInterface::STATE_PENDING], []],
      [4, [
        OgMembershipInterface::STATE_ACTIVE,
        OgMembershipInterface::STATE_PENDING,
        OgMembershipInterface::STATE_BLOCKED,
      ], [],
      ],
    ];
  }

  /**
   * Tests that memberships are deleted when a user is deleted.
   */
  public function testOrphanedMembershipsDeletion() {
    foreach ($this->users as $user) {
      // Keep track of the user ID before deleting the user.
      $user_id = $user->id();

      $user->delete();

      // Check that the memberships for the user are deleted from the database.
      $memberships = $this->entityTypeManager
        ->getStorage('og_membership')
        ->getQuery()
        ->condition('uid', $user_id)
        ->execute();

      $this->assertEmpty($memberships);
    }
  }

}
