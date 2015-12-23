<?php

/**
 * @file
 * Contains Drupal\Tests\og\Kernel\Entity\OgRoleTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgRole;

/**
 * Test OG role creation.
 *
 * @group og
 */
class OgRoleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Installing needed schema.
    $this->installConfig(['og']);
  }

  /**
   * Testing OG role creation.
   */
  public function testRoleCreate() {
    $og_role = OgRole::create();
    $og_role
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->grantPermission('administer group')
      ->save();

    // Checking creation of the role.
    $this->assertEquals($og_role->getPermissions(), ['administer group']);

    // Try to create the same role again.
    try {
      $og_role = OgRole::create();
      $og_role
        ->setId('content_editor')
        ->setLabel('Content editor')
        ->grantPermission('administer group')
        ->save();

      $this->fail('OG role with the same ID can be saved.');
    }
    catch (EntityStorageException $e) {
      $this->assertTrue(TRUE, "OG role with the same ID can not be saved.");
    }

    // Create a role assigned to a group type.
    $og_role = OgRole::create();
    $og_role
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->setGroupType('entity_test')
      ->setGroupBundle('group')
      ->save();

    $this->assertEquals('entity_test-group-content_editor', $og_role->id());

    // Try to create the same role again.
    try {
      $og_role = OgRole::create();
      $og_role
        ->setId('content_editor')
        ->setLabel('Content editor')
        ->setGroupType('entity_test')
        ->setGroupBundle('group')
        ->save();

      $this->fail('OG role with the same ID on the same group can be saved.');
    }
    catch (EntityStorageException $e) {
      $this->assertTrue(TRUE, "OG role with the same ID on the same group can not be saved.");
    }
  }

}
