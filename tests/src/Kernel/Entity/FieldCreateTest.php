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
   * @var NodeType
   */
  protected $bundle1;

  /**
   * @var NodeType
   */
  protected $bundle2;

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

    // Create a two bundles.
    $this->bundle1 = NodeType::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $this->bundle1->save();

    $this->bundle2 = NodeType::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $this->bundle2->save();
  }

  /**
   * Testing field creation.
   */
  public function testValidFields() {
    // Simple creation.

    // Override the field config.
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $this->bundle1->id(), ['instance' => ['label' => 'Other groups dummy']]);
    $this->assertEquals(FieldConfig::loadByName('node', $this->bundle1->id(), OG_AUDIENCE_FIELD)->label(), 'Other groups dummy');

    // Override the field storage config.
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $this->bundle2->id(), [
      'field' => ['field_name' => 'og_group_ref_dummy'],
      'instance' => ['field_name' => 'og_group_ref_dummy'],
    ]);
    $this->assertEquals(FieldConfig::loadByName('node', $this->bundle2->id(), 'og_group_ref_dummy')->id(), 'node.' . $this->bundle2->id() . '.og_group_ref_dummy');
  }



}
