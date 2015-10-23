<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgDefaultAccessFieldTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test default field access.
 *
 * @group og
 */
class OgDefaultAccessFieldTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * Test groups with default access field enabled or disabled.
   */
  function testOgDefaultAccessField() {
    // Create user.
    $user1 = $this->drupalCreateUser();

    // Add OG group field to the entity_test's "main" bundle.
    og_create_field(OG_GROUP_FIELD, 'entity_test', 'main');

    $og_roles = og_roles('entity_test', 'main');

    // Group without default access field.
    $entity = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();
    $this->assertEqual($og_roles, og_roles('entity_test', 'main', $entity->pid), t('Group without default access field is assigned to the global roles and permissions settings.'));

    // Add default access field to the entity_test's "main" bundle.
    og_create_field(OG_DEFAULT_ACCESS_FIELD, 'entity_test', 'main');

    // Group with default access field disabled.
    $entity = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->{OG_DEFAULT_ACCESS_FIELD}->set(0);
    $wrapper->save();
    $this->assertEqual($og_roles, og_roles('entity_test', 'main', $entity->pid), t('Group with default access field disabled is assigned to the global roles and permissions settings.'));

    // Add admin role to a user.
    $rid = array_search(OG_ADMINISTRATOR_ROLE, $og_roles);
    og_role_grant('entity_test', $entity->pid, $user1->uid, $rid);
    $user_roles = og_get_user_roles('entity_test', $entity->pid, $user1->uid);
    $this->assertTrue(array_search(OG_ADMINISTRATOR_ROLE, $user_roles), t('User has default "admin" role.'));

    // Group with default access field enabled.
    $wrapper->{OG_DEFAULT_ACCESS_FIELD}->set(1);
    $wrapper->save();
    $new_og_roles = og_roles('entity_test', 'main', $entity->pid);
    $this->assertNotEqual($og_roles, $new_og_roles, t('Group with default access field enabled has own roles and permissions settings.'));

    // Assert the newley created admin role was mapped to the default one.
    $user_roles = og_get_user_roles('entity_test', $entity->pid, $user1->uid, FALSE);
    $this->assertTrue(array_search(OG_ADMINISTRATOR_ROLE, $user_roles), t('User has overriden "admin" role.'));

    // Disable existing group's default access field.
    variable_set('og_maintain_overridden_roles', TRUE);
    $wrapper->{OG_DEFAULT_ACCESS_FIELD}->set(0);
    $wrapper->save();
    $this->assertEqual($og_roles, og_roles('entity_test', 'main', $entity->pid), t('Group with enabled default access field that was disabled is assigned to the global roles and permissions settings.'));

    // Assert admin role was maintained from the overriden group.
    $user_roles = og_get_user_roles('entity_test', $entity->pid, $user1->uid, FALSE);
    $this->assertTrue(array_search(OG_ADMINISTRATOR_ROLE, $user_roles), t('"admin" role maintained from overriden group.'));

    // Override group.
    $wrapper->{OG_DEFAULT_ACCESS_FIELD}->set(1);
    $wrapper->save();

    // Assert admin role was not maintained from the overriden group.
    variable_set('og_maintain_overridden_roles', FALSE);
    $wrapper->{OG_DEFAULT_ACCESS_FIELD}->set(0);
    $wrapper->save();

    $user_roles = og_get_user_roles('entity_test', $entity->pid, $user1->uid, FALSE);
    $this->assertFalse(array_search(OG_ADMINISTRATOR_ROLE, $user_roles), t('"admin" role not maintained from overriden group.'));
  }

}
