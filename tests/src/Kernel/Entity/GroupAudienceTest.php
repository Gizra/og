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
    $bundle = $this->bundles[0];

    $field_name1 = Unicode::strtolower($this->randomMachineName());
    $field_name2 = Unicode::strtolower($this->randomMachineName());

    Og::CreateField(OG_AUDIENCE_FIELD, 'entity_test', $bundle, ['field_name' => $field_name1]);
    Og::CreateField(OG_AUDIENCE_FIELD, 'entity_test', $bundle, ['field_name' => $field_name2]);

    $field_names = Og::getAllGroupAudienceFields('entity_test', $bundle);

    $this->assertEquals(array_keys($field_names), array($field_name1, $field_name2));
  }


}
