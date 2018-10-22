<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\og\Og;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the creation of og_audience fields.
 *
 * @group og_migrate
 */
class OgAudienceD6MigrateTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
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
    $this->migrateContentTypes();
    $this->executeMigrations([
      'd6_og_group_type',
      'd6_og_audience',
    ]);
  }

  /**
   * Asserts that fields are created on content types.
   */
  public function testOgAudienceFields() {
    $this->assertTrue(Og::isGroupContent('node', 'story'), 'Created node.story.og_audience');
    $this->assertTrue(Og::isGroupContent('node', 'test_planet'), 'Created node.test_planet.og_audience');
    $this->assertFalse(Og::isGroupContent('node', 'page'), 'Did not create node.page.og_audience');
  }

}
