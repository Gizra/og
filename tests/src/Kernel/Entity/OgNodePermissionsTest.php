<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Test OG permissions for node entities.
 *
 * @group og
 */
class OgNodePermissionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'og',
    'system',
    'user',
  ];

  /**
   * Tests group content node permissions.
   */
  public function testGetPermissions() {
    // Create a node group content.
    $bundle = NodeType::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $bundle->save();
    Og::CreateField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $bundle->id());

    $name = $bundle->id();
    $expected = [
      'administer group',
      'update group',
      "create $name content",
      "delete any $name content",
      "delete own $name content",
      "delete $name revisions",
      "edit any $name content",
      "edit own $name content",
      "revert $name revisions",
      "view $name revisions",
    ];

    $permissions = Og::permissionHandler()->getPermissions();

    $this->assertEquals($expected, array_keys($permissions));
  }

}
