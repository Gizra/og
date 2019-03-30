<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests organic group membership (group content) migration.
 *
 * @group og_migrate
 */
class OgEntityMembershipD7MigrateTest extends MigrateDrupal7TestBase {

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
    'forum',
    'system',
    'taxonomy',
    'user',
    'og',
    'og_ui',
    'og_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal7.php');
    $this->installEntitySchema('og_membership_type');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('og_role');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('comment_type');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installConfig(['comment', 'user', 'node', 'og']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('forum', ['forum_index', 'forum']);
    $this->installSchema('node', ['node_access']);
    $this->executeMigrations([
      'd7_filter_format',
      'd7_user_role',
      'd7_user',
      'd7_node_type',
      'd7_node:test_content_type',
      'd7_node:article',
      'd7_node:forum',
      'd7_comment_type',
      'd7_comment_field',
      'd7_comment_field_instance',
      'd7_comment',
      'd7_taxonomy_vocabulary',
      'd7_taxonomy_term:test_vocabulary',
      'd7_taxonomy_term:tags',
      'd7_taxonomy_term:sujet_de_discussion',
      'd7_og_group',
      'd7_og_role',
      'd7_field',
      'd7_field_instance',
      'd7_og_field_instance',
      'd7_og_membership_type',
      'd7_og_entity_membership',
    ]);
  }

  /**
   * Asserts that the og_membership entities have been saved.
   */
  public function testOgMembership() {
    $storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('node');

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $results = $storage->getQuery()
      ->exists('og_audience')
      ->execute();

    $this->assertCount(4, $results, 'Group content migrated');
  }

}
