<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\entity_test\Entity\EntityTestWithBundle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\user\Entity\User;

/**
 * Tests retrieving groups associated with a given group content.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\MembershipManager
 */
class GroupMembershipManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'node',
    'og',
    'options',
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
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_with_bundle');
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->groups = [];

    // Create group admin user.
    $group_admin = User::create(['name' => $this->randomString()]);
    $group_admin->save();

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
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    $result = $membership_manager->getGroupIds($this->groupContent, $group_type_id, $group_bundle);

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
   * Tests that the static cache loads the appropriate group.
   *
   * Verify that entities from different entity types with colliding Ids that
   * point to different groups do not confuse the membership manager.
   *
   * @covers ::getGroups
   */
  public function testStaticCache() {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
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

    $group_content_rev_group = $membership_manager->getGroups($group_content_rev);
    /** @var \Drupal\node\NodeInterface $group */
    $group = reset($group_content_rev_group['node']);
    $this->assertEquals($this->groups['node'][0]->id(), $group->id());
    $group_content_with_bundle_group = $membership_manager->getGroups($group_content_with_bundle);
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
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    $result = $membership_manager->getGroups($this->groupContent, $group_type_id, $group_bundle);

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
  public function testGetGroupCount($group_type_id, $group_bundle, array $expected) {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    $result = $membership_manager->getGroupCount($this->groupContent, $group_type_id, $group_bundle);

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
