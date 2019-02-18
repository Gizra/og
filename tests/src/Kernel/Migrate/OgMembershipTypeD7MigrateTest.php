<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\og\Entity\OgMembershipType;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests organic groups membership type migration.
 *
 * @group og_migrate
 */
class OgMembershipTypeD7MigrateTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['text', 'system', 'og', 'og_migrate'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership_type');
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal7.php');
    $this->executeMigrations(['d7_og_membership_type']);
  }

  /**
   * Asserts that the membership type and its form and view displays were migrated.
   */
  public function testOgMembershipType() {
    $type = OgMembershipType::load('default');

    $this->assertInstanceOf('\Drupal\og\Entity\OgMembershipType', $type);
    $this->assertEquals('Custom default description.', $type->label());

    $types = OgMembershipType::loadMultiple();

    $this->assertCount(1, $types, 'Migrated default migration into Drupal 8 default.');

    $form_display = EntityFormDisplay::load('og_membership.default.default');

    $this->assertNotEqual(NULL, $form_display);

    $view_display = EntityViewDisplay::load('og_membership.default.default');

    $this->assertNotEqual(null, $view_display);
  }

}
