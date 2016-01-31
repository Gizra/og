<?php

/**
 * @file
 * Contains \Drupal\og_ui\Tests\BundleFormAlterTest.
 */

namespace Drupal\og_ui\Tests;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\KernelTests\AssertLegacyTrait;
use Drupal\simpletest\AssertContentTrait;
use Drupal\simpletest\BrowserTestBase;

/**
 * Test making a bundle a group and a group content.
 *
 * @group og
 */
class BundleFormAlterTest extends BrowserTestBase {

  use AssertContentTrait;
  use AssertLegacyTrait;

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
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm($edit, t('Save content type'));
    $edit = [
      'name' => 'class',
      'type' => 'class',
      'og_group_content_bundle' => 1,
      'og_target_type' => 'node',
      'og_target_bundles[]' => ['school'],
    ];
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm($edit, t('Save content type'));
    $this->content = $this->drupalGet('admin/structure/types/manage/class');
    $this->assertOptionSelected('edit-og-target-bundles', 'school');
    $this->assertTargetBundles(['school' => 'school'], 'The target bundles are set to the "school" bundle.');

    // Test that if the target bundles are unselected, the value for the target
    // bundles becomes NULL rather than an empty array. The entity reference
    // selection plugin considers the value NULL to mean 'all bundles', while an
    // empty array means 'no bundles are allowed'.
    // @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection::buildEntityQuery()
    $edit = [
      'name' => 'class',
      'og_group_content_bundle' => 1,
      'og_target_type' => 'node',
      'og_target_bundles[]' => [],
    ];
    $this->drupalGet('admin/structure/types/manage/class');
    $this->submitForm($edit, t('Save content type'));
    $this->assertTargetBundles(NULL, 'When the target bundle field is cleared from all values, it takes on the value NULL.');
  }

  /**
   * Checks whether the target bundles in the group content are as expected.
   *
   * @param array|NULL $expected
   *   The expected value for the target bundles.
   * @param $message
   *   The message to display with the assertion.
   */
  protected function assertTargetBundles($expected, $message) {
    /** @var EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');
    $entity_field_manager->clearCachedFieldDefinitions();
    $field_definitions = $entity_field_manager->getFieldDefinitions('node', 'class');
    $settings = $field_definitions['og_group_ref']->getSetting('handler_settings');
    $this->assertEquals($expected, $settings['target_bundles'], $message);
  }

}
