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
   * @var OgRole
   */
  protected $ogRole;

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
    $this->ogRole = OgRole::create();
    $this->ogRole
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->setPermissions(['administer group'])
      ->setUid(1)
      ->save();

    // Checking assigning of the role.
    $this->assertEquals($this->ogRole->getPermissions(), ['administer group']);
  }

}
