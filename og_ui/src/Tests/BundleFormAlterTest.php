<?php

/**
 * @file
 * Contains \Drupal\og_ui\Tests\BundleFormAlterTest.
 */

namespace Drupal\og_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test making a bundle a group and a group content.
 *
 * @group og
 */
class BundleFormAlterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'og_ui'];

  public function testCreate() {
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types'));
    $this->drupalLogin($web_user);
    $edit = [
      'name' => 'school',
      'type' => 'school',
      'og_is_group' => 1,
    ];
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save content type'));
    $edit = [
      'name' => 'class',
      'type' => 'class',
      'og_group_content_bundle' => 1,
      'og_target_type' => 'node',
      'og_target_bundles[]' => ['school'],
    ];
    $this->drupalPostForm('admin/structure/types/add', $edit, t('Save content type'));
    $this->drupalGet('admin/structure/types/manage/class');
    $this->assertOptionSelected('edit-og-target-bundles', 'school');
  }

}
