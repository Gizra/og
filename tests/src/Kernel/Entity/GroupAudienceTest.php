<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Tests the group audience field.
 *
 * @group og
 */
class GroupAudienceTest extends KernelTestBase {

  /**
   * The OG group audience helper.
   *
   * @var \Drupal\og\OgGroupAudienceHelperInterface
   */
  protected $groupAudienceHelper;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_reference',
    'entity_test',
    'field',
    'og',
    'system',
    'user',
  ];

  /**
   * Array with the bundle IDs.
   *
   * @var array
   */
  protected $bundles;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');

    $this->groupAudienceHelper = $this->container->get('og.group_audience_helper');

    // Create several bundles.
    for ($i = 0; $i <= 4; $i++) {
      $bundle = EntityTest::create([
        'type' => mb_strtolower($this->randomMachineName()),
        'name' => $this->randomString(),
      ]);

      $bundle->save();
      $this->bundles[] = $bundle->id();
    }
  }

  /**
   * Testing getting all group audience fields.
   */
  public function testGetAllGroupAudienceFields() {
    // Set bundles as groups.
    Og::groupTypeManager()->addGroup('entity_test', $this->bundles[0]);
    Og::groupTypeManager()->addGroup('entity_test', $this->bundles[1]);

    $bundle = $this->bundles[2];

    // Test no values returned for a non-group content.
    $this->assertEmpty($this->groupAudienceHelper->getAllGroupAudienceFields('entity_test', $bundle));

    // Set bundles as group content.
    $field_name1 = mb_strtolower($this->randomMachineName());
    $field_name2 = mb_strtolower($this->randomMachineName());

    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', $bundle, ['field_name' => $field_name1]);
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', $bundle, ['field_name' => $field_name2]);

    $expected_field_names = [$field_name1, $field_name2];
    $actual_field_names = array_keys($this->groupAudienceHelper->getAllGroupAudienceFields('entity_test', $bundle));
    sort($expected_field_names);
    sort($actual_field_names);

    $this->assertEquals($expected_field_names, $actual_field_names);

    // Test Og::isGroupContent method, which is just a wrapper around
    // OgGroupAudienceHelper::hasGroupAudienceFields().
    $this->assertTrue(Og::isGroupContent('entity_test', $bundle));

    $bundle = $this->bundles[3];
    $this->assertFalse(Og::isGroupContent('entity_test', $bundle));
  }

  /**
   * Testing getting group audience fields filtered by group type.
   */
  public function testGetAllGroupAudienceFieldsFilterGroupType() {
    Og::groupTypeManager()->addGroup('entity_test', $this->bundles[0]);

    $bundle = $this->bundles[1];

    // Set bundle as group content.
    $field_name1 = mb_strtolower($this->randomMachineName());
    $field_name2 = mb_strtolower($this->randomMachineName());

    $overrides = [
      'field_name' => $field_name1,
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'user',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', $bundle, $overrides);

    // Add a default field, which will use the "entity_test" as target type.
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', $bundle, ['field_name' => $field_name2]);

    $field_names = $this->groupAudienceHelper->getAllGroupAudienceFields('entity_test', $bundle, 'entity_test');
    $this->assertEquals([$field_name2], array_keys($field_names));
  }

  /**
   * Testing getting group audience fields filtered by group bundle.
   */
  public function testGetAllGroupAudienceFieldsFilterGroupBundle() {
    // Set bundles as groups.
    Og::groupTypeManager()->addGroup('entity_test', $this->bundles[0]);
    Og::groupTypeManager()->addGroup('entity_test', $this->bundles[1]);

    $group_bundle1 = $this->bundles[0];
    $group_bundle2 = $this->bundles[1];

    $bundle = $this->bundles[2];

    // Set bundle as group content.
    $field_name1 = mb_strtolower($this->randomMachineName());
    $field_name2 = mb_strtolower($this->randomMachineName());

    // Add fields that explicitly references a bundle.
    $overrides = [
      'field_name' => $field_name1,
      'field_config' => [
        'settings' => [
          'handler' => 'default',
          'handler_settings' => [
            'target_bundles' => [$group_bundle1 => $group_bundle1],
          ],
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', $bundle, $overrides);

    $overrides['field_name'] = $field_name2;
    $overrides['field_config']['settings']['handler_settings']['target_bundles'] = [$group_bundle2 => $group_bundle2];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'entity_test', $bundle, $overrides);

    $field_names = $this->groupAudienceHelper->getAllGroupAudienceFields('entity_test', $bundle, 'entity_test', $group_bundle1);
    $this->assertEquals([$field_name1], array_keys($field_names));
  }

}
