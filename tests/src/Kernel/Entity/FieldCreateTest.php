<?php

/**
 * @file
 * Contains Drupal\Tests\og\Kernel\Entity\FieldCreateTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;

/**
 * Testing field definition overrides.
 *
 * @group og
 */
class FieldCreateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'field', 'entity_reference', 'node', 'og', 'system'];

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
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create three bundles.
    for ($i = 0; $i <= 3; $i++) {
      $bundle = NodeType::create([
        'type' => Unicode::strtolower($this->randomMachineName()),
        'name' => $this->randomString(),
      ]);

      $bundle->save();
      $this->bundles[] = $bundle->id();
    }
  }

  /**
   * Testing field creation.
   */
  public function testValidFields() {
    // Simple create.
    $bundle = $this->bundles[0];
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $bundle);
    $this->assertNotNull(FieldConfig::loadByName('node', $bundle, OG_AUDIENCE_FIELD));

    // Override the field config.
    $bundle = $this->bundles[1];
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $bundle, ['field_config' => ['label' => 'Other groups dummy']]);
    $this->assertEquals(FieldConfig::loadByName('node', $bundle, OG_AUDIENCE_FIELD)->label(), 'Other groups dummy');

    // Override the field storage config.
    $bundle = $this->bundles[2];
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $bundle, ['field_name' => 'override_name']);
    $this->assertNotNull(FieldConfig::loadByName('node', $bundle, 'override_name')->id());
  }

  /**
   * Testing invalid field creation.
   */
  public function testInvalidFields() {
    $bundle = $this->bundles[0];
    try {
      Og::CreateField('undefined_field_name', 'node', $bundle, ['field_config' => ['label' => 'Other groups dummy']]);
      $this->fail('Undefined field name was attached');
    }
    catch (\Exception $e) {
    }

  }
}
