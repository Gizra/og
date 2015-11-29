<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\GroupAudienceTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\Component\Utility\Unicode;

/**
 * @group og
 */
class GroupAudienceTest extends KernelTestBase {


  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'user', 'field', 'entity_reference', 'og', 'system'];

  /**
   * @var Array
   *   Array with the bundle IDs.
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

    // Create several bundles.
    for ($i = 0; $i <= 4; $i++) {
      $bundle = EntityTest::create([
        'type' => Unicode::strtolower($this->randomMachineName()),
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
    Og::groupManager()->addGroup('entity_test', $this->bundles[0]);
    Og::groupManager()->addGroup('entity_test', $this->bundles[1]);

    $bundle = $this->bundles[2];

    // Test no values returned for a non-group content.
    $this->assertEmpty(Og::getAllGroupAudienceFields('entity_test', $bundle));

    // Set bundles as group content.
    $field_name1 = Unicode::strtolower($this->randomMachineName());
    $field_name2 = Unicode::strtolower($this->randomMachineName());

    Og::CreateField(OG_AUDIENCE_FIELD, 'entity_test', $bundle, ['field_name' => $field_name1]);
    Og::CreateField(OG_AUDIENCE_FIELD, 'entity_test', $bundle, ['field_name' => $field_name2]);

    $field_names = Og::getAllGroupAudienceFields('entity_test', $bundle);
    $this->assertEquals(array($field_name1, $field_name2), array_keys($field_names));

    // Test filtering by group name.
    $field_names = Og::getAllGroupAudienceFields('entity_test', $bundle, 'entity_test');
    $this->assertEquals(array($field_name1, $field_name2), array_keys($field_names));


    $field_names = Og::getAllGroupAudienceFields('entity_test', $bundle, 'entity_test', $this->bundles[0]);
    $this->assertEquals(array($field_name1, $field_name2), array_keys($field_names));

    // Add field that explicitly references a bundle.
    $field_name3 = Unicode::strtolower($this->randomMachineName());
    $overrides = [
      'field_name' => $field_name3,
      'handler_settings' => [
        'target_bundles' => [$this->bundles[3]]
      ],
    ];
    Og::CreateField(OG_AUDIENCE_FIELD, 'entity_test', $bundle, $overrides);
    $field_names = Og::getAllGroupAudienceFields('entity_test', $bundle, 'entity_test', $this->bundles[3]);
    $this->assertEquals(array($field_name3), array_keys($field_names));
  }
}
