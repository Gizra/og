<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\Plugin\OgFields\AccessField;

/**
 * Testing field definition overrides.
 *
 * @group og
 */
class FieldCreateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'field',
    'node',
    'options',
    'system',
    'og',
    'og_test',
  ];

  /**
   * The group related bundles.
   *
   * @var array
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

    // Create several bundles.
    for ($i = 0; $i <= 4; $i++) {
      $bundle = NodeType::create([
        'type' => mb_strtolower($this->randomMachineName()),
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
    // Simple create, for all the fields defined by OG core.
    $field_names = [
      OgGroupAudienceHelperInterface::DEFAULT_FIELD,
      AccessField::DEFAULT_FIELD,
    ];

    foreach ($field_names as $field_name) {
      $bundle = $this->bundles[0];
      Og::CreateField($field_name, 'node', $bundle);
      $this->assertNotNull(FieldConfig::loadByName('node', $bundle, $field_name));
    }

    // Override the field config.
    $bundle = $this->bundles[1];
    Og::CreateField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $bundle, ['field_config' => ['label' => 'Other groups dummy']]);
    $this->assertEquals(FieldConfig::loadByName('node', $bundle, OgGroupAudienceHelperInterface::DEFAULT_FIELD)->label(), 'Other groups dummy');

    // Override the field storage config.
    $bundle = $this->bundles[2];
    Og::CreateField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $bundle, ['field_name' => 'override_name']);
    $this->assertNotNull(FieldConfig::loadByName('node', $bundle, 'override_name')->id());

    // Field that can be added only to certain entities.
    $bundle = $this->bundles[3];
    Og::CreateField('entity_restricted', 'node', $bundle);
    $this->assertNotNull(FieldConfig::loadByName('node', $bundle, 'entity_restricted')->id());
  }

  /**
   * Testing invalid field creation.
   */
  public function testInvalidFields() {
    // Unknown plugin.
    $bundle = $this->bundles[0];
    try {
      Og::CreateField('undefined_field_name', 'node', $bundle);
      $this->fail('Undefined field name was attached');
    }
    catch (\Exception $e) {
    }

    // Field that can be attached only to a certain entity type, being attached
    // to another one.
    try {
      Og::CreateField('entity_restricted', 'user', 'user');
      $this->fail('Field was attached to a prohibited entity type.');
    }
    catch (\Exception $e) {
    }
  }

}
