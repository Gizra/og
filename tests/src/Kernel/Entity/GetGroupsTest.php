<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;

/**
 * Tests retrieving groups associated with a given group content.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class GetGroupsTest extends KernelTestBase {

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
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface entityTypeManager */
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->groups = [];

    // Create four groups of two different entity types.
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
      $this->groups['node'][] = $group;

      // The Entity Test entity doesn't have 'real' bundles, so we don't need to
      // create one, we can just add the group to the fake bundle.
      $bundle = "entity_test_$i";
      Og::groupManager()->addGroup('entity_test', $bundle);

      $group = EntityTest::create([
        'type' => $bundle,
        'name' => $this->randomString(),
      ]);
      $group->save();
      $this->groups['entity_test'][] = $group;
    }

    // Create a group content type with two group audience fields, one for each
    // group.
    $bundle = Unicode::strtolower($this->randomMachineName());
    foreach (['entity_test', 'node'] as $target_type) {
      $settings = [
        'field_name' => 'group_audience_' . $target_type,
        'field_storage_config' => [
          'settings' => [
            'target_type' => $target_type,
          ],
        ],
      ];
      Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'entity_test', $bundle, $settings);
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
    $result = Og::getGroupIds($this->groupContent, $group_type_id, $group_bundle);

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
    $result = Og::getGroups($this->groupContent, $group_type_id, $group_bundle);

    // Check that the correct number of results is returned.
    $this->assertEquals(count($expected, COUNT_RECURSIVE) - count($expected), count($result));

    // Check that all expected results are returned.
    foreach ($expected as $expected_type => $expected_keys) {
      foreach ($expected_keys as $expected_key) {
        /** @var \Drupal\Core\Entity\EntityInterface $expected_group */
        $expected_group = $this->groups[$expected_type][$expected_key];
        foreach ($result as $key => $group) {
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
