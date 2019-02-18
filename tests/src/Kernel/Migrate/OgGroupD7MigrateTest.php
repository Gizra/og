<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\og\GroupTypeManager;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests that entity types and bundles that are groups are configured.
 *
 * @group og_migrate
 */
class OgGroupD7MigrateTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
    'filter',
    'menu_ui',
    'node',
    'system',
    'user',
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

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['node', 'og']);
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal7.php');
    $this->executeMigrations([
      'd7_filter_format',
      'd7_user_role',
      'd7_user',
      'd7_taxonomy_vocabulary',
      'd7_taxonomy_term',
      'd7_node_type',
      'd7_node',
      'd7_og_group',
    ]);
  }

  /**
   * Asserts that the og_membership entities have been saved.
   */
  public function testOgGroup() {
    $expected = [
      'node' => ['test_content_type'],
      'taxonomy_term' => ['test_vocabulary'],
    ];
    $groups = $this->container
      ->get('config.factory')
      ->get(GroupTypeManager::SETTINGS_CONFIG_KEY)
      ->get(GroupTypeManager::GROUPS_CONFIG_KEY);

    $this->assertEquals($expected, $groups);
  }

}
