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
    $groups = [];

    // Create two groups of different entity types.
    $bundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'name' => $this->randomString(),
      'type' => $bundle,
    ])->save();
    Og::groupManager()->addGroup('node', $bundle);

    $groups['node'] = Node::create([
      'title' => $this->randomString(),
      'type' => $bundle,
    ]);
    $groups['node']->save();

    // The Entity Test entity doesn't have 'real' bundles, so we don't need to
    // create one, we can just add the group to the fake bundle.
    $bundle = Unicode::strtolower($this->randomMachineName());
    Og::groupManager()->addGroup('entity_test', $bundle);

    $groups['entity_test'] = EntityTest::create([
      'type' => $bundle,
      'name' => $this->randomString(),
    ]);
    $groups['entity_test']->save();

    // Create 4 group content types, two for each entity type referencing each
    // group. Create a group content entity for each.
    $group_content = [];
    foreach (['node', 'entity_test'] as $entity_type) {
      foreach (['node', 'entity_test'] as $target_group_type) {
        // Create the group content bundle if it's a node. Entity Test doesn't
        // have real bundles.
        $bundle = Unicode::strtolower($this->randomMachineName());
        if ($entity_type === 'node') {
          NodeType::create([
            'type' => $bundle,
            'name' => $this->randomString(),
          ])->save();
        }

        // Create the groups audience field.
        $field_name = "og_$target_group_type";
        $settings = [
          'field_name' => $field_name,
          'field_storage_config' => [
            'settings' => [
              'target_type' => $groups[$target_group_type]->getEntityTypeId(),
            ],
          ],
          'field_config' => [
            'settings' => [
              'handler_settings' => [
                'target_bundles' => [$groups[$target_group_type]->bundle()  => $groups[$target_group_type]->bundle()],
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
          $field_name => [['target_id' => $groups[$target_group_type]->id()]],
        ]);
        $entity->save();

        $group_content[$entity_type][$target_group_type] = $entity;
      }
    }

    // Check that Og::getGroupContent() returns the correct group content for
    // each group.
    foreach (['node', 'entity_test'] as $group_type) {
      $result = Og::getGroupContent($groups[$group_type]);
      foreach (['node', 'entity_test'] as $group_content_type) {
        $this->assertEquals([$group_content[$group_content_type][$group_type]->id()], $result[$group_content_type], "The correct $group_content_type group content is returned for the $group_type group.");
      }
    }
  }

}
