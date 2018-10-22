<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\og\GroupTypeManager;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests that entity types and bundles that are groups are configured.
 *
 * @group og_migrate
 */
class OgGroupD6MigrateTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
    'entity_reference',
    'menu_ui',
    'node',
    'og',
    'og_ui',
    'og_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['node', 'og']);
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal6.php');
    $this->executeMigrations([
      'd6_node_type',
      'd6_og_group_type',
    ]);
  }

  /**
   * Asserts that the og_membership entities have been saved.
   */
  public function testOgGroup() {
    $expected = [
      'node' => ['company'],
    ];
    $groups = $this->container
      ->get('config.factory')
      ->get(GroupTypeManager::SETTINGS_CONFIG_KEY)
      ->get(GroupTypeManager::GROUPS_CONFIG_KEY);

    $this->assertEquals($expected, $groups);
  }

}
