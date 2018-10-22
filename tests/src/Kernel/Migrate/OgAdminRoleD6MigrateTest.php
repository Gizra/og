<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\og\Entity\OgRole;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of admin roles for Drupal 6.
 *
 * @group og_migrate
 */
class OgAdminRoleD6MigrateTest extends MigrateDrupal6TestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
    'menu_ui',
    'node',
    'system',
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
    $this->installEntitySchema('node');
    $this->installConfig(['user', 'node', 'og']);
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal6.php');
    $this->executeMigrations([
      'd6_node_type',
      'd6_og_group_type',
      'd6_og_admin_role',
    ]);
  }

  /**
   * Asserts that an "administrator member" role was created.
   */
  public function testOgAdminRole() {
    $roles = OgRole::loadByGroupType('node', 'company');

    $this->assertCount(1, $roles, 'Found 1 standard role in node.company');

    $admin_role = array_shift($roles);

    $this->assertEquals('node-company-administrator-member', $admin_role->id());
  }

}
