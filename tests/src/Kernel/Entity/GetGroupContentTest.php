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
 * Tests getting the group content of a group.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class GetGroupContentTest extends KernelTestBase {

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
  }

  /**
   * Test retrieval of group content that references a single group.
   */
  public function testBasicGroupReferences() {
    // Create two groups of different entity types.
    $node_group_bundle = 'node_g_' . Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'name' => $this->randomString(),
      'type' => $node_group_bundle,
    ])->save();
    Og::groupManager()->addGroup('node', $node_group_bundle);

    $node_group = Node::create([
      'title' => $this->randomString(),
      'type' => $node_group_bundle,
    ]);
    $node_group->save();

    $entity_test_group_bundle = 'entity_test_g' . Unicode::strtolower($this->randomMachineName());
    Og::groupManager()->addGroup('entity_test', $entity_test_group_bundle);

    $entity_test_group = EntityTest::create([
      'type' => $entity_test_group_bundle,
      'name' => $this->randomString(),
    ]);
    $entity_test_group->save();

    // Create 4 group content types, two for each entity type referencing each
    // group. Create a group content entity for each.
    $group_content = [];
    foreach (['node', 'entity_test'] as $entity_type) {
      foreach (['node_group', 'entity_test_group'] as $target_group) {
        // Create the group content type.
        $bundle = $entity_type . Unicode::strtolower($this->randomMachineName());
        if ($entity_type === 'node') {
          NodeType::create([
            'type' => $bundle,
            'name' => $this->randomString(),
          ])->save();
        }
        $settings = [
          'field_name' => Unicode::strtolower($this->randomMachineName()),
          'field_storage_config' => [
            'settings' => [
              'handler_settings' => [
                'target_bundles' => [$$target_group->bundle()  => $$target_group->bundle()],
                'target_type' => $$target_group->getEntityTypeId(),
              ],
            ],
          ],
        ];
        Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, $entity_type, $bundle, $settings);

        // Create the group content entity.
        $label_field = $entity_type === 'node' ? 'title' : 'name';
        $entity = $this->entityTypeManager->getStorage($entity_type)->create([
          $label_field => $this->randomString(),
          'type' => $bundle,
          OgGroupAudienceHelper::DEFAULT_FIELD => [['target_id' => $$target_group->id()]],
        ]);
        $entity->save();

        $group_content[$entity_type][$target_group] = $entity;
      }
    }

    // Check that Og::getGroupContent() returns the correct group content for
    // each group.
    foreach (['node_group', 'entity_test_group'] as $group) {
      $result = Og::getGroupContent($$group);

    }
  }

}
