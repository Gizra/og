<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests retrieving groups associated with a given group content.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\MembershipManager
 */
class GroupMembershipManagerTest extends KernelTestBase {

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
   * @var \Drupal\Core\Entity\EntityInterface[][]
   */
  protected $groups;

  /**
   * Test group content.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $groupContent;

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
   * The membership manager. This is the system under test.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installSchema('user', 'users_data');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->membershipManager = $this->container->get('og.membership_manager');

    $this->groups = [];

    // Create group admin user.
    $group_admin = User::create(['name' => $this->randomString()]);
    $group_admin->save();
    $this->users[0] = $group_admin;

    // Create four groups of two different entity types.
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
      $this->groups['node'][] = $group;

      // The Entity Test entity doesn't have 'real' bundles, so we don't need to
      // create one, we can just add the group to the fake bundle.
      $bundle = "entity_test_$i";
      Og::groupTypeManager()->addGroup('entity_test', $bundle);

      $group = EntityTest::create([
        'type' => $bundle,
        'name' => $this->randomString(),
        'uid' => $group_admin->id(),
      ]);
      $group->save();
      $this->groups['entity_test'][] = $group;
    }

    // Create a group content type with two group audience fields, one for each
    // group.
    $bundle = mb_strtolower($this->randomMachineName());
    foreach (['entity_test', 'node'] as $target_type) {
      $settings = [
        'field_name' => 'group_audience_' . $target_type,
        'field_storage_config' => [
          'settings' => [
            'target_type' => $target_type,
          ],
        ],
      ];
      Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', $bundle, $settings);
    }

    // Create a group content entity that references all four groups.
    $values = [
      'name' => $this->randomString(),
      'type' => $bundle,
    ];
    foreach (['entity_test', 'node'] as $target_type) {
      foreach ($this->groups[$target_type] as $group) {
        $values['group_audience_' . $target_type][] = [
          'target_id' => $group->id(),
        ];
      }
    }

    $this->groupContent = $this->entityTypeManager->getStorage('entity_test')->create($values);
    $this->groupContent->save();
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
  public function testGetGroupIds($group_type_id, $group_bundle, array $expected) {
    $result = $this->membershipManager->getGroupIds($this->groupContent, $group_type_id, $group_bundle);

    // Check that the correct number of results is returned.
    $this->assertEquals(count($expected, COUNT_RECURSIVE), count($result, COUNT_RECURSIVE));

    // Check that all expected results are returned.
    foreach ($expected as $expected_type => $expected_keys) {
      foreach ($expected_keys as $expected_key) {
        $this->assertTrue(in_array($this->groups[$expected_type][$expected_key]->id(), $result[$expected_type]));
      }
    }
  }

  /**
   * Tests that exceptions are thrown when invalid arguments are passed.
   *
   * @covers ::getGroupIds
   * @dataProvider groupContentProvider
   */
  public function testGetGroupIdsInvalidArguments() {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    $test_cases = [
      // Test that an exception is thrown when passing a User entity.
      User::create(),
      // Test that an exception is thrown when passing an entity which is not
      // group content. We are using one of the test groups for this.
      $this->groups['node'][0],
    ];

    foreach ($test_cases as $test_case) {
      try {
        $membership_manager->getGroupIds($test_case);
        $this->fail();
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }
    }
  }

  /**
   * Tests that the static cache loads the appropriate group.
   *
   * Verify that entities from different entity types with colliding Ids that
   * point to different groups do not confuse the membership manager.
   *
   * @covers ::getGroups
   */
  public function testStaticCache() {
    $bundle_rev = mb_strtolower($this->randomMachineName());
    $bundle_with_bundle = mb_strtolower($this->randomMachineName());
    EntityTestBundle::create(['id' => $bundle_with_bundle, 'label' => $bundle_with_bundle])->save();
    $field_settings = [
      'field_name' => 'group_audience_node',
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'node',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test_rev', $bundle_rev, $field_settings);
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test_with_bundle', $bundle_with_bundle, $field_settings);

    $group_content_rev = EntityTestRev::create([
      'type' => $bundle_rev,
      'name' => $this->randomString(),
      'group_audience_node' => [
        0 => [
          'target_id' => $this->groups['node'][0]->id(),
        ],
      ],
    ]);
    $group_content_rev->save();
    $group_content_with_bundle = EntityTestWithBundle::create([
      'type' => $bundle_with_bundle,
      'name' => $this->randomString(),
      'group_audience_node' => [
        0 => [
          'target_id' => $this->groups['node'][1]->id(),
        ],
      ],
    ]);
    $group_content_with_bundle->save();

    // Ensure that both entities share the same Id. This is an assertion to
    // ensure that the next assertions are addressing the proper issue.
    $this->assertEquals($group_content_rev->id(), $group_content_with_bundle->id());

    $group_content_rev_group = $this->membershipManager->getGroups($group_content_rev);
    /** @var \Drupal\node\NodeInterface $group */
    $group = reset($group_content_rev_group['node']);
    $this->assertEquals($this->groups['node'][0]->id(), $group->id());
    $group_content_with_bundle_group = $this->membershipManager->getGroups($group_content_with_bundle);
    $group = reset($group_content_with_bundle_group['node']);
    $this->assertEquals($this->groups['node'][1]->id(), $group->id());
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
  public function testGetGroups($group_type_id, $group_bundle, array $expected) {
    $result = $this->membershipManager->getGroups($this->groupContent, $group_type_id, $group_bundle);

    // Check that the correct number of results is returned.
    $this->assertEquals(count($expected, COUNT_RECURSIVE), count($result, COUNT_RECURSIVE));

    // Check that all expected results are returned.
    foreach ($expected as $expected_type => $expected_keys) {
      foreach ($expected_keys as $expected_key) {
        /** @var \Drupal\Core\Entity\EntityInterface $expected_group */
        $expected_group = $this->groups[$expected_type][$expected_key];
        /** @var \Drupal\Core\Entity\EntityInterface $group */
        foreach ($result[$expected_type] as $group) {
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
  public function testGetGroupCount($group_type_id, $group_bundle, array $expected) {
    $result = $this->membershipManager->getGroupCount($this->groupContent, $group_type_id, $group_bundle);

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

  /**
   * Tests retrieval of group memberships filtered by role names.
   *
   * @covers ::getGroupMembershipsByRoleNames
   */
  public function testGetGroupMembershipsByRoleNames() {
    $retrieve_membership_owner_id = function (OgMembershipInterface $membership) {
      return $membership->getOwnerId();
    };
    $this->doTestGetGroupMembershipsByRoleNames('getGroupMembershipsByRoleNames', $retrieve_membership_owner_id);
  }

  /**
   * Tests retrieval of group membership IDs filtered by role names.
   *
   * @covers ::getGroupMembershipIdsByRoleNames
   */
  public function testGetGroupMembershipIdsByRoleNames() {
    $membership_storage = $this->container->get('entity_type.manager')->getStorage('og_membership');
    $retrieve_membership_owner_id = function ($membership_id) use ($membership_storage) {
      /** @var \Drupal\og\OgMembershipInterface $membership */
      $membership = $membership_storage->load($membership_id);
      return $membership->getOwnerId();
    };
    $this->doTestGetGroupMembershipsByRoleNames('getGroupMembershipIdsByRoleNames', $retrieve_membership_owner_id);
  }

  /**
   * Tests retrieval of group memberships or their IDs filtered by role names.
   *
   * Contains the actual test logic of ::testGetGroupMembershipsByRoleNames()
   * and ::testGetGroupMembershipIdsByRoleNames().
   *
   * @param string $method_name
   *   The name of the method under test. Can be one of the following:
   *   - 'getGroupMembershipIdsByRoleNames'
   *   - 'getGroupMembershipsByRoleNames'.
   * @param callable $retrieve_membership_owner_id
   *   A callable that will retrieve the ID of the owner of the membership or
   *   membership ID.
   */
  protected function doTestGetGroupMembershipsByRoleNames($method_name, callable $retrieve_membership_owner_id) {
    $this->createTestMemberships();

    // Check that an exception is thrown if no role names are passed.
    try {
      $this->membershipManager->$method_name($this->groups['node'][0], []);
      $this->fail('MembershipManager::getGroupsMembershipsByRoleNames() throws an exception when called without passing any role names.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }

    // Define a test matrix to iterate over. We're not using a data provider
    // because the large number of test cases would slow down the test too much.
    // The test matrix has the following structure:
    // @code
    // [
    //   // The machine name of the group entity type being tested.
    //   {entity_type_id} => [
    //     // The key of the test group as created in ::setUp().
    //     {group_key} => [ //
    //       // The roles being passed to the method.
    //       'roles' => [{role_id}],
    //       // The membership states being passed to the method.
    //       'states' => [{state_id}],
    //       // The memberships that should be returned by the method.
    //       'expected_memberships' => [{expected_membership_id}],
    //     ],
    //   ],
    // ];
    // @endcode
    $matrix = [
      'node' => [
        0 => [
          // All memberships with all possible states. The authenticated role
          // covers all memberships.
          [
            'roles' => [OgRoleInterface::AUTHENTICATED],
            'states' => OgMembershipInterface::ALL_STATES,
            'expected_memberships' => [0, 1, 4, 7],
          ],
          // All memberships with all possible states, by passing an empty
          // states array, and passing all defined roles.
          [
            'roles' => [
              OgRoleInterface::AUTHENTICATED,
              OgRoleInterface::ADMINISTRATOR,
              'moderator',
            ],
            'states' => [],
            'expected_memberships' => [0, 1, 4, 7],
          ],
          // Pending members.
          [
            'roles' => [OgRoleInterface::AUTHENTICATED],
            'states' => [OgMembershipInterface::STATE_PENDING],
            'expected_memberships' => [4],
          ],
        ],
        1 => [
          // All administrators.
          [
            'roles' => [OgRoleInterface::ADMINISTRATOR],
            'states' => [],
            'expected_memberships' => [2, 6],
          ],
          // Pending administrators.
          [
            'roles' => [OgRoleInterface::ADMINISTRATOR],
            'states' => [OgMembershipInterface::STATE_PENDING],
            'expected_memberships' => [2],
          ],
          // Blocked administrators. There are none.
          [
            'roles' => [OgRoleInterface::ADMINISTRATOR],
            'states' => [OgMembershipInterface::STATE_BLOCKED],
            'expected_memberships' => [],
          ],
          // Pending and blocked administrators and moderators. Should be the
          // same result as the pending administrators, since there are no
          // moderators or blocked users.
          [
            'roles' => [OgRoleInterface::ADMINISTRATOR, 'moderator'],
            'states' => [
              OgMembershipInterface::STATE_BLOCKED,
              OgMembershipInterface::STATE_PENDING,
            ],
            'expected_memberships' => [2],
          ],
          // Switch the order of the arguments, this should not affect the
          // result.
          [
            'roles' => ['moderator', OgRoleInterface::ADMINISTRATOR],
            'states' => [
              OgMembershipInterface::STATE_PENDING,
              OgMembershipInterface::STATE_BLOCKED,
            ],
            'expected_memberships' => [2],
          ],
          // There are no pending or blocked moderators.
          [
            'roles' => ['moderator'],
            'states' => [
              OgMembershipInterface::STATE_BLOCKED,
              OgMembershipInterface::STATE_PENDING,
            ],
            'expected_memberships' => [],
          ],
        ],
      ],
      'entity_test' => [
        0 => [
          // The first test entity group doesn't have any moderators or admins.
          // Check that duplicated array values doesn't affect the result.
          [
            'roles' => [
              'moderator',
              OgRoleInterface::ADMINISTRATOR,
              'moderator',
              'moderator',
              OgRoleInterface::ADMINISTRATOR,
            ],
            'states' => [
              OgMembershipInterface::STATE_ACTIVE,
              OgMembershipInterface::STATE_BLOCKED,
              OgMembershipInterface::STATE_PENDING,
              OgMembershipInterface::STATE_PENDING,
              OgMembershipInterface::STATE_BLOCKED,
              OgMembershipInterface::STATE_ACTIVE,
            ],
            'expected_memberships' => [],
          ],
        ],
        // Check active members.
        [
          'roles' => [
            OgRoleInterface::AUTHENTICATED,
          ],
          'states' => [
            OgMembershipInterface::STATE_ACTIVE,
          ],
          'expected_memberships' => [0, 3],
        ],
        1 => [
          // There are two blocked users in the second test entity group.
          [
            'roles' => [
              OgRoleInterface::AUTHENTICATED,
              OgRoleInterface::ADMINISTRATOR,
              'moderator',
            ],
            'states' => [
              OgMembershipInterface::STATE_BLOCKED,
            ],
            'expected_memberships' => [4, 7],
          ],
          // There is one pending administrator, just as in the node group with
          // the same entity ID. This ensures that the correct result will be
          // returned for groups that have different entity types but the same
          // entity ID.
          [
            'roles' => [OgRoleInterface::ADMINISTRATOR],
            'states' => [OgMembershipInterface::STATE_PENDING],
            'expected_memberships' => [8],
          ],
          // There is one blocked moderator.
          [
            'roles' => [
              OgRoleInterface::ADMINISTRATOR,
              'moderator',
            ],
            'states' => [
              OgMembershipInterface::STATE_BLOCKED,
            ],
            'expected_memberships' => [4],
          ],
        ],
      ],
    ];

    foreach ($matrix as $entity_type_id => $groups) {
      foreach ($groups as $group_key => $test_cases) {
        foreach ($test_cases as $test_case) {
          $group = $this->groups[$entity_type_id][$group_key];
          $role_names = $test_case['roles'];
          $states = $test_case['states'];
          $expected_memberships = $test_case['expected_memberships'];

          $actual_memberships = $this->membershipManager->$method_name($group, $role_names, $states);
          $this->assertSameSize($expected_memberships, $actual_memberships);

          foreach ($expected_memberships as $expected_membership_key) {
            $expected_user_id = $this->users[$expected_membership_key]->id();
            foreach ($actual_memberships as $actual_membership) {
              if ($retrieve_membership_owner_id($actual_membership) == $expected_user_id) {
                // Match found.
                continue 2;
              }
            }
            // The expected membership was not returned. Fail the test.
            $this->fail("The membership for user $expected_membership_key is present in the result.");
          }
        }
      }
    }

    // Test that the correct memberships are returned when one of the users is
    // deleted. We are using the second node group as a test case. This group
    // has one pending administrator: the user with key '2'.
    $group = $this->groups['node'][1];
    $role_names = [OgRoleInterface::ADMINISTRATOR];
    $states = [OgMembershipInterface::STATE_PENDING];
    $memberships = $this->membershipManager->$method_name($group, $role_names, $states);
    $this->assertCount(1, $memberships);

    // Delete the user and check that it no longer appears in the result.
    $this->users[2]->delete();
    $memberships = $this->membershipManager->$method_name($group, $role_names, $states);
    $this->assertCount(0, $memberships);
  }

  /**
   * Creates a number of users that are members of the test groups.
   */
  protected function createTestMemberships() {
    // Create a 'moderator' role in each of the test group types.
    foreach (['node', 'entity_test'] as $entity_type_id) {
      for ($i = 0; $i < 2; $i++) {
        $bundle = "${entity_type_id}_$i";
        $og_role = OgRole::create();
        $og_role
          ->setName('moderator')
          ->setGroupType($entity_type_id)
          ->setGroupBundle($bundle)
          ->save();
      }
    }

    // Create test users with different membership states in the various groups.
    // Note that the group admin (test user 0) is also a member of all groups.
    $matrix = [
      // A user which is an active member of the first node group.
      1 => [
        'node' => [
          0 => [
            'state' => OgMembershipInterface::STATE_ACTIVE,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
        ],
      ],

      // A user which is a pending administrator of the second node group.
      2 => [
        'node' => [
          1 => [
            'state' => OgMembershipInterface::STATE_PENDING,
            'roles' => [OgRoleInterface::ADMINISTRATOR],
          ],
        ],
      ],

      // A user which is an active member of both test entity groups.
      3 => [
        'entity_test' => [
          0 => [
            'state' => OgMembershipInterface::STATE_ACTIVE,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
          1 => [
            'state' => OgMembershipInterface::STATE_ACTIVE,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
        ],
      ],

      // A user which is a pending member of the first node group and a blocked
      // moderator in the second test entity group.
      4 => [
        'node' => [
          0 => [
            'state' => OgMembershipInterface::STATE_PENDING,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
        ],
        'entity_test' => [
          1 => [
            'state' => OgMembershipInterface::STATE_BLOCKED,
            'roles' => ['moderator'],
          ],
        ],
      ],

      // A user which is not subscribed to any of the groups.
      5 => [],

      // A user which is both an administrator and a moderator in the second
      // node group.
      6 => [
        'node' => [
          1 => [
            'state' => OgMembershipInterface::STATE_ACTIVE,
            'roles' => [OgRoleInterface::ADMINISTRATOR, 'moderator'],
          ],
        ],
      ],

      // A troll who is banned everywhere.
      7 => [
        'node' => [
          0 => [
            'state' => OgMembershipInterface::STATE_BLOCKED,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
          1 => [
            'state' => OgMembershipInterface::STATE_BLOCKED,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
        ],
        'entity_test' => [
          0 => [
            'state' => OgMembershipInterface::STATE_BLOCKED,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
          1 => [
            'state' => OgMembershipInterface::STATE_BLOCKED,
            'roles' => [OgRoleInterface::AUTHENTICATED],
          ],
        ],
      ],

      // A user which is a pending administrator of the second test entity
      // group.
      8 => [
        'entity_test' => [
          1 => [
            'state' => OgMembershipInterface::STATE_PENDING,
            'roles' => [OgRoleInterface::ADMINISTRATOR],
          ],
        ],
      ],
    ];

    foreach ($matrix as $user_key => $entity_types) {
      $user = User::create(['name' => $this->randomString()]);
      $user->save();
      $this->users[$user_key] = $user;
      foreach ($entity_types as $entity_type_id => $groups) {
        foreach ($groups as $group_key => $membership_info) {
          $group = $this->groups[$entity_type_id][$group_key];
          $this->createOgMembership($group, $user, $membership_info['roles'], $membership_info['state']);
        }
      }
    }
  }

}
