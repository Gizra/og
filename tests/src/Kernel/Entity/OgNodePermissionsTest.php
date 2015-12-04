<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\OgNodePermissionsTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
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
  public static $modules = ['node', 'field', 'og', 'system'];

  /**
   * Array with the bundle IDs.
   *
   * @var Array
   */
  protected $bundles;


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
  }

  /**
   * Testing group content node permissions.
   */
  public function testGetPermissions() {
    // Create a node group content.
    $name = $this->randomString();
    $bundle = NodeType::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $name,
    ]);

    $bundle->save();
    Og::CreateField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', $bundle->id());

    $expected = [
      'administer group',
      'update group',
      'create article content',
      'delete any article content',
      'delete own article content',
      'delete article revisions',
      'edit any article content',
      'edit own article content',
      'revert article revisions',
      'view article revisions',
    ];

    $permissions = Og::permissionHandler()->getPermissions();

    $this->assertEquals($expected, array_keys($permissions));
  }

}
