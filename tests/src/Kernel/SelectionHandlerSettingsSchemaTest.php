<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the config schema for OG selection handler settings.
 *
 * @group og
 */
class SelectionHandlerSettingsSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'og',
    'og_ui',
    'system',
    'user',
  ];

  /**
   * Tests the config schema for OG selection handler settings.
   */
  public function testSelectionHandlerSettingsSchema() {
    $node_type = NodeType::create([
      'type' => $bundle = strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    // We are using the 'og_ui' mechanism for adding the audience field.
    // @see og_ui_entity_type_save().
    $node_type->og_group_content_bundle = TRUE;
    $node_type->og_is_group = FALSE;
    $node_type->og_target_type = 'node';
    $node_type->og_target_bundles = [$bundle];
    $node_type->save();

    // This test leverages the ConfigSchemaChecker that is included in the core
    // test runner to validate that the schema is correct. However PHPUnit will
    // mark this test as risky if we do not do any assertions. Work around this
    // by including a fake assertion.
    $this->assertTrue(TRUE);
  }

}
