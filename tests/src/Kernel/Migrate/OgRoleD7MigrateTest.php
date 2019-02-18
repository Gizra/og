<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\og\Entity\OgRole;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests organic group role migration.
 *
 * @group og_migrate
 */
class OgRoleD7MigrateTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
    'filter',
    'menu_ui',
    'node',
    'system',
    'taxonomy',
    'og',
    'og_ui',
    'og_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('og_role');
    $this->installConfig(['node', 'og']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal7.php');
    $this->executeMigrations([
      'd7_filter_format',
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node',
      'd7_taxonomy_vocabulary',
      'd7_taxonomy_term',
      'd7_og_group',
      'd7_og_role',
    ]);
  }

  /**
   * Asserts that the og_role entities have been saved.
   */
  public function testOgRole() {
    $roles = OgRole::loadMultiple();

    $this->assertEquals(7, count($roles), '7 roles were migrated.');

    // Asserts that the non-member role has subscribe permission.
    $anonymousRole = OgRole::load('node-test_content_type-non-member');
    $this->assertEquals(['subscribe'], $anonymousRole->getPermissions());

    // Asserts permissions on administrative role.
    $adminRole = OgRole::load('node-test_content_type-administrator');
    $expected = [
      'add user',
      'administer group',
      'approve and deny subscription',
      'manage members',
      'manage permissions',
      'manage roles',
      'update group',
    ];
    $this->assertEquals($expected, $adminRole->getPermissions());

    // Asserts permissions for content creator role.
    $contentRole = OgRole::load('node-test_content_type-content-creator');
    $expected = [
      'create article content',
      'create page content',
    ];
    $this->assertEquals($expected, $contentRole->getPermissions());
  }

}
