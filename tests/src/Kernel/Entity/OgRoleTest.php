<?php

/**
 * @file
 * Contains Drupal\Tests\og\Kernel\Entity\OgRoleTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

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
  public function testSelectionHandler() {
    $og_role = OgRole::create();
    $og_role
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->grantPermission('administer group')
      ->save();

    // Checking creation of the role.
    $this->assertEquals($og_role->getPermissions(), ['administer group']);

    // Create a role assigned to a group type.
    $og_role = OgRole::create();
    $og_role
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->setGroupType('entity_test')
      ->setGroupBundle('group')
      ->save();

    $this->assertEquals('entity_test-group-content_editor', $og_role->id());
  }

}
