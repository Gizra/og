<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the GroupType condition plugin.
 *
 * @group og
 */
class GroupTypeConditionTest extends KernelTestBase {

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
  protected $groups;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->conditionManager = $this->container->get('plugin.manager.condition');
    $this->groupTypeManager = $this->container->get('og.group_type_manager');

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    // Create three test groups of different types.
    for ($i = 0; $i < 2; $i++) {
      $bundle = "node$i";

      NodeType::create([
        'name' => $this->randomString(),
        'type' => $bundle,
      ])->save();
      $this->groupTypeManager->addGroup('node', $bundle);

      $group = Node::create([
        'title' => $this->randomString(),
        'type' => $bundle,
      ]);
      $group->save();

      $this->groups[$bundle] = $group;
    }

    // The Entity Test entity doesn't have 'real' bundles, so we don't need to
    // create one, we can just add the group to the fake bundle.
    $bundle = 'entity_test';
    $this->groupTypeManager->addGroup('entity_test', $bundle);

    $group = EntityTest::create([
      'type' => $bundle,
      'name' => $this->randomString(),
    ]);
    $group->save();
    $this->groups[$bundle] = $group;
  }

  /**
   * Tests conditions.
   *
   * @dataProvider conditionsProvider
   */
  public function testConditions($group_types, $negate, $context_value, $expected) {
    // Create an instance of the group type condition plugin.
    /** @var \Drupal\og\Plugin\Condition\GroupType $plugin_instance */

    $plugin_instance = $this->conditionManager->createInstance('og_group_type')
      ->setConfig('group_types', array_combine($group_types, $group_types))
      ->setConfig('negate', $negate)
      ->setContextValue('og', $this->groups[$context_value]);

    $this->assertEquals($expected, $plugin_instance->execute());
  }

  /**
   * Data provider for ::testConditions().
   *
   * @return array
   *   An indexed array with the following elements:
   *   - 0: An array of group types that are configured in the plugin, in the
   *     format '{entity_type_id}-{bundle_id}'.
   *   - 1: A boolean indicating whether or not the plugin is configured to
   *     negate the condition.
   *   - 2: The ID of the test group that is present on the route context.
   *   - 3: A boolean indicating whether or not the condition is expected to be
   *     true.
   */
  public static function conditionsProvider() {
    return [
      [
        // The plugin is configured to act on group type node, bundle node0.
        ['node-node0'],
        // It's configuration is not negated.
        FALSE,
        // Our test group 'node0' is available as context.
        'node0',
        // So the condition is expected to match.
        TRUE,
      ],
      [
        ['node-node1'],
        TRUE,
        'node1',
        FALSE,
      ],
      [
        ['entity_test-entity_test'],
        TRUE,
        'node1',
        TRUE,
      ],
      [
        ['node-node0', 'node-node1'],
        FALSE,
        'entity_test',
        FALSE,
      ],
      [
        ['node-node0', 'node-node1', 'entity_test-entity_test'],
        FALSE,
        'entity_test',
        TRUE,
      ],
      [
        ['node-node0', 'node-node1'],
        TRUE,
        'node0',
        FALSE,
      ],
    ];
  }

}
