<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgPermissionsTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test OG permissions.
 *
 * @group og
 */
class OgPermissionsTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    // Add OG group field to the entity_test's "main" bundle.
    og_create_field(OG_GROUP_FIELD, 'entity_test', 'main');

    // Add OG audience field to the node's "article" bundle.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'entity_test';
    og_create_field(OG_AUDIENCE_FIELD, 'node', 'article', $og_field);
  }


  /**
   * Verify proper permission changes by og_role_change_permissions().
   */
  function testOgUserRoleChangePermissions() {
    // Create user.
    $user1 = $this->drupalCreateUser();

    // Create an entity.
    $entity = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    // Associate user to the group.
    $user2 = $this->drupalCreateUser();
    $values = array('entity_type' => 'user', 'entity' => $user2);
    og_group('entity_test', $entity->pid, $values);

    // Assert the user is registered to the new group.
    $this->assertTrue(og_is_member('entity_test', $entity->pid, 'user', $user2), t('User is registered to the new group.'));

    // Verify current permissions.
    $this->assertFalse(og_user_access('entity_test', $entity->pid, 'update own article content', $user2), t('User does not have "update own article content" permission.'));
    $this->assertFalse(og_user_access('entity_test', $entity->pid, 'delete own article content', $user2), t('User does not have "delete own article content" permission.'));

    // Change permissions to authenticated member.
    $og_roles = array_flip(og_roles('entity_test', 'main', $entity->pid));
    // Authenticated role ID.
    $rid = $og_roles[OG_AUTHENTICATED_ROLE];

    $permissions = array(
      'delete own article content' => 1,
    );
    og_role_change_permissions($rid, $permissions);

    // Verify proper permission changes.
    $this->assertFalse(og_user_access('entity_test', $entity->pid, 'update own article content', $user2), t('User still does not have "update own article content" permission.'));
    $this->assertTrue(og_user_access('entity_test', $entity->pid, 'delete own article content', $user2), t('User now has "delete own article content" permission.'));

    $permissions = array(
      'delete own article content' => 0,
      'administer group' => 1,
    );
    og_role_change_permissions($rid, $permissions);

    $this->assertTrue(og_user_access('entity_test', $entity->pid, 'delete own article content', $user2), t('User still has "delete own article content" as they have "administer group" permission.'));
    $this->assertTrue(og_user_access('entity_test', $entity->pid, 'administer group', $user2), t('User has "administer group" permission.'));
  }

  /**
   * Assert blocked and pending roles influence the allowed permissions.
   */
  function testBlockedAndPendingRoles() {
    // Create user.
    $user1 = $this->drupalCreateUser();

    // Create an entity.
    $entity = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    // Associate user to the group, and grant "admin" role.
    $user2 = $this->drupalCreateUser();
    $values = array('entity_type' => 'user', 'entity' => $user2);
    og_group('entity_test', $entity->pid, $values);
    $og_roles = og_roles('entity_test', 'main');

    $rid = array_search(OG_ADMINISTRATOR_ROLE, $og_roles);
    og_role_grant('entity_test', $entity->pid, $user2->uid, $rid);

    // Active member.
    $roles = og_get_user_roles('entity_test', $entity->pid, $user2->uid);
    $expected_result = array(
      array_search(OG_AUTHENTICATED_ROLE, $og_roles) => OG_AUTHENTICATED_ROLE,
      array_search(OG_ADMINISTRATOR_ROLE, $og_roles) => OG_ADMINISTRATOR_ROLE,
    );
    $this->assertEqual($roles, $expected_result, 'Active member has also the admin role.');
    $this->assertTrue(og_user_access('entity_test', $entity->pid, 'update group', $user2), 'Active member has access.');

    // Pending member.
    $values['state'] = OG_STATE_PENDING;
    og_group('entity_test', $entity->pid, $values);
    $roles = og_get_user_roles('entity_test', $entity->pid, $user2->uid);
    $rid = array_search(OG_ANONYMOUS_ROLE, $og_roles);
    $expected_result = array($rid => OG_ANONYMOUS_ROLE);
    $this->assertEqual($roles, $expected_result, 'Pending member has non-member role.');
    $this->assertFalse(og_user_access('entity_test', $entity->pid, 'update group', $user2), 'Pending member has no access.');

    // Blocked member.
    $values['state'] = OG_STATE_BLOCKED;
    og_group('entity_test', $entity->pid, $values);
    $roles = og_get_user_roles('entity_test', $entity->pid, $user2->uid);
    $this->assertEqual($roles,  array(), 'Blocked member has no roles.');
    $this->assertFalse(og_user_access('entity_test', $entity->pid, 'update group', $user2), 'Blocked member has no access.');
  }

}
