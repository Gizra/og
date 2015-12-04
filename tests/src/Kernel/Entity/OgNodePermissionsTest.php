<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\OgNodePermissionsTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\Component\Utility\Unicode;
use Drupal\og\OgGroupAudienceHelper;

/**
 * @group og
 */
class OgNodePermissionsTest extends KernelTestBase {


  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'field', 'og', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install needed config and schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
  }

  /**
   * Testing group content node permissions.
   */
  public function testGetPermissions() {
    // Create a node group content.
    $bundle = NodeType::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $bundle->save();
    Og::CreateField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', $bundle->id());

    $label = $bundle->label();
    $expected = [
      'administer group',
      'update group',
      "create $label content",
      "delete any $label content",
      "delete own $label content",
      "delete $label revisions",
      "edit any $label content",
      "edit own $label content",
      "revert $label revisions",
      "view $label revisions",
    ];

    $permissions = Og::permissionHandler()->getPermissions();

    $this->assertEquals($expected, array_keys($permissions));
  }

}
