<?php

namespace Drupal\Tests\og\Kernel\Migrate;

use Drupal\og\Entity\OgMembership;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests organic group membership migration for users.
 *
 * @group og_migrate
 */
class OgUserMembershipD6MigrateTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'text',
    'entity_reference',
    'filter',
    'menu_ui',
    'node',
    'system',
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

    $this->installEntitySchema('og_membership_type');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('og_role');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['user', 'node', 'og']);
    $this->loadFixture(__DIR__ . '/../../../fixtures/drupal6.php');
    $this->executeMigrations([
      'd6_filter_format',
      'd6_user_role',
      'd6_user',
      'd6_node_settings',
      'd6_node_type',
      'd6_node',
      'd6_og_group_type',
      'd6_og_admin_role',
      'd6_og_user_membership',
    ]);
  }

  /**
   * Asserts that the og_membership entities have been saved.
   */
  public function testOgMembership() {
    $memberships = OgMembership::loadMultiple();

    $this->assertEquals(7, count($memberships), 'Seven members were migrated.');
    $bundles = array_reduce($memberships, function (&$result, OgMembershipInterface $membership) {
      if (!in_array($membership->bundle(), $result)) {
        $result[] = $membership->bundle();
      }
      return $result;
    }, []);

    $this->assertEquals(['default'], $bundles, 'Default membership type migrated.');

    // Gets user 2, entity ID 14 membership.
    $membership = array_reduce($memberships, function (&$result, OgMembershipInterface $membership) {
      if ($result === NULL && $membership->uid->target_id == 2 &&
          $membership->entity_id->value == 14 &&
          $membership->entity_type->value === 'node' &&
          $membership->entity_bundle->value === 'company') {
        $result = $membership;
      }
      return $result;
    }, NULL);

    $this->assertNotNull($membership);
    $this->assertTrue($membership->hasRole('node-company-member'), 'User 2 has member role.');
    $this->assertTrue($membership->hasRole('node-company-administrator'), 'User 2 has administrator role.');
  }

}
