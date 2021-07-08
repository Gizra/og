<?php

namespace Drupal\Tests\og_migrate\Kernel\Migrate;

use Drupal\node\Entity\Node;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests that node memberships are migrated.
 *
 * @group og_migrate
 *
 * @internal
 */
class OgNodeMembershipD6MigrateTest extends MigrateDrupal6TestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
    'entity_reference',
    'filter',
    'menu_ui',
    'node',
    'user',
    'og',
    'og_ui',
    'og_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('og_membership_type');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['node', 'og']);
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal6.php');
    $this->migrateContentTypes();
    $this->migrateUsers();
    $this->migrateContent();
    $this->executeMigrations([
      'd6_og_group_type',
      'd6_og_audience',
      'd6_og_node_membership',
    ]);
  }

  /**
   * Asserts that group content exists.
   */
  public function testGroupContent() {
    $storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('node');
    $results = $storage->getQuery()
      ->exists('og_audience')
      ->execute();

    $this->assertCount(7, $results, 'Found 7 nodes with og_audience field data.');

    $node = Node::load(8);
    $this->assertCount(2, $node->get('og_audience'), 'Found migrated node in multiple groups.');
  }

}
