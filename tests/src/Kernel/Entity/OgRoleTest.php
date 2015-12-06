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

    // Add membership and config schema.
    $this->installConfig(['og']);
  }

  /**
   * Testing OG role creation.
   */
  public function testSelectionHandler() {
    $role = OgRole::create();
    $role
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->setPermissions(['bypass content restrictions'])
      ->setUid(1);
    $role->save();
  }

}
