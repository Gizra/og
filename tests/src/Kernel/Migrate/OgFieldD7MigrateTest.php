<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\og\Entity\OgMembership;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\migrate\Kernel\NodeCommentCombinationTrait;
use Drupal\Tests\file\Kernel\Migrate\d7\FileMigrationSetupTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests organic group field migration.
 *
 * @group og_migrate
 */
class OgFieldD7MigrateTest extends MigrateDrupal7TestBase {

  use NodeCommentCombinationTrait;
  use FileMigrationSetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
    'entity_reference',
    'telephone',
    'datetime',
    'image',
    'link',
    'filter',
    'menu_ui',
    'comment',
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

    $this->fileMigrationSetup();
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal7.php');

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(static::$modules);

    $this->createNodeCommentCombination('page');
    $this->createNodeCommentCombination('article');
    $this->createNodeCommentCombination('blog');
    $this->createNodeCommentCombination('book');
    $this->createNodeCommentCombination('forum', 'comment_forum');
    $this->createNodeCommentCombination('test_content_type');

    Vocabulary::create(['vid' => 'test_vocabulary'])->save();

    $this->executeMigrations([
      'd7_field',
      'd7_field_instance',
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node',
      'd7_og_group',
      'd7_og_field_instance',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo() {
    return [
      'path' => 'public://sites/default/files/cube.jpeg',
      'size' => '3620',
      'base_path' => 'public://',
      'plugin_id' => 'd7_file',
    ];
  }

  /**
   * Asserts that the og-related fields were migrated properly.
   */
  public function testOgAudienceFields() {
    $nodeField = FieldConfig::load('node.article.og_audience');
    $fieldDefinitions = $this->container->get('entity_field.manager')->getFieldDefinitions('node', 'article');

    $this->assertEquals('og_standard_reference', $nodeField->getType());
    $this->assertArrayHasKey('og_audience', $fieldDefinitions);

    $forumField = FieldConfig::load('node.forum.og_audience');
    $fieldDefinitions = $this->container->get('entity_field.manager')->getFieldDefinitions('node', 'forum');

    $this->assertEquals('og_standard_reference', $forumField->getType());
    $this->assertArrayHasKey('og_audience', $fieldDefinitions);
  }

}
