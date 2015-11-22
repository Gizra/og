<?php

/**
 * @file
 * Contains Drupal\Tests\og\Kernel\Entity\SelectionHandlerTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;

/**
 * Tests entity reference selection plugins.
 *
 * @group og
 */
class FieldOverrideTest extends KernelTestBase {

  /**
   * The selection handler.
   *
   * @var \Drupal\og\Plugin\EntityReferenceSelection\OgSelection.
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'field', 'entity_reference', 'node', 'og', 'system'];

  /**
   * @var string
   *
   * The machine name of the group node type.
   */
  protected $groupBundle;

  /**
   * @var string
   *
   * The machine name of the group content node type.
   */
  protected $groupContentBundle;

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
   * Testing field definition overrides.
   */
  public function testSelectionHandlerResults() {
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $this->bundle1->id(), ['instance' => ['label' => 'Other groups dummy']]);
    Og::CreateField(OG_AUDIENCE_FIELD, 'node', $this->bundle2->id(), [
      'field' => ['field_name' => 'og_group_ref_dummy'],
      'instance' => ['field_name' => 'og_group_ref_dummy'],
    ]);

    $this->assertEquals(FieldConfig::loadByName('node', $this->bundle1->id(), OG_AUDIENCE_FIELD)->label(), 'Other groups dummy');
    $this->assertEquals(FieldConfig::loadByName('node', $this->bundle2->id(), 'og_group_ref_dummy')->id(), 'node.' . $this->bundle2->id() . '.og_group_ref_dummy');
  }

}
