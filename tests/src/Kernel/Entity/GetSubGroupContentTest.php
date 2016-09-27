<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\user\Entity\User;

/**
 * Tests getting the group content of a group.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Og
 */
class GetSubGroupContentTest extends KernelTestBase {

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
   * The group admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

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

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->entityTypeManager = $entity_type_manager;

    // Create group admin user.
    $this->groupAdmin = User::create(['name' => $this->randomString()]);
    $this->groupAdmin->save();
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
    Og::groupTypeManager()->addGroup('node', $bundle);

    $groups['node'] = Node::create([
      'title' => $this->randomString(),
      'type' => $bundle,
      'uid' => $this->groupAdmin->id(),
    ]);
    $groups['node']->save();

    // The Entity Test entity doesn't have 'real' bundles, so we don't need to
    // create one, we can just add the group to the fake bundle.
    $bundle = Unicode::strtolower($this->randomMachineName());
    Og::groupTypeManager()->addGroup('entity_test', $bundle);

    $groups['entity_test'] = EntityTest::create([
      'type' => $bundle,
      'name' => $this->randomString(),
      'uid' => $this->groupAdmin->id(),
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
                'target_bundles' => [$groups[$target_group_type]->bundle() => $groups[$target_group_type]->bundle()],
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

    // Each piece of group content has only one parent group.
    foreach ($group_content as $entity_type => $targets) {
      foreach ($targets as $target_group_type => $entity) {
        $parent_groups = Og::getParentGroups($entity);
        $this->assertEquals(1, count($parent_groups));
      }
    }

    foreach ($groups as $group) {
      $parent_groups = Og::getParentGroups($group);
      $this->assertEquals(1, count($parent_groups));
      $this->assertEquals($group, end($parent_groups), 'Group has only itself as parent groups');
    }
  }

  /**
   * Test nested groups of the same type.
   */
  public function testNestedGroups() {
    // Create two groups of different entity types.
    $bundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'name' => $this->randomString(),
      'type' => $bundle,
    ])->save();
    Og::groupTypeManager()->addGroup('node', $bundle);

    // Create the groups audience field.
    $field_name = "og_$bundle";
    $settings = [
      'field_name' => $field_name,
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'node',
        ],
      ],
      'field_config' => [
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [$bundle => $bundle],
          ],
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', $bundle, $settings);
    $groups = [];
    $expected_parent_count = [];
    for ($i = 0; $i < 5; $i++) {
      $values = [
        'title' => $this->randomString(),
        'type' => $bundle,
      ];
      if ($i > 0) {
        // Make this group a sub group of the one we created before.
        $values[$field_name] = [['target_id' => $groups[$i]->id()]];
      }
      $entity = $this->entityTypeManager->getStorage('node')->create($values);
      $entity->save();
      $groups[$i + 1] = $entity;
      $expected_parent_count[$entity->id()] = $i + 1;
    }
    // Create one more group as a subgroup that will have 4 parents plus itself.
    $values = [
      'title' => $this->randomString(),
      'type' => $bundle,
      $field_name => [['target_id' => $groups[4]->id()]],
    ];
    $entity = $this->entityTypeManager->getStorage('node')->create($values);
    $entity->save();
    $groups[] = $entity;
    $expected_parent_count[$entity->id()] = 5;
    foreach ($groups as $idx => $node) {
      $parent_groups = Og::getParentGroups($node);
      $this->assertEquals($expected_parent_count[$node->id()], count($parent_groups));
    }
  }

}
