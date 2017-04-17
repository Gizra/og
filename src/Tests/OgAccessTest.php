<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgAccessTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test access operations.
 *
 * @group og
 */
class OgAccessTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * Verify og_user_access_entity() returns correct value.
   */
  function testOgAccessEntity() {
    $perm = 'administer group';
    // Change permissions to authenticated member.

    // Add OG group fields.
    og_create_field(OG_GROUP_FIELD, 'entity_test', 'main');
    $roles = array_flip(og_roles('entity_test', 'main'));
    og_role_change_permissions($roles[OG_AUTHENTICATED_ROLE], array($perm => 1));


    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'entity_test';
    og_create_field(OG_AUDIENCE_FIELD, 'node', 'article', $og_field);

    $user1 = $this->drupalCreateUser();
    $user2 = $this->drupalCreateUser();
    $user3 = $this->drupalCreateUser();

    // Create a group.
    $entity1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    // User has access to group.
    $this->assertTrue(og_user_access_entity($perm, 'entity_test', $entity1, $user1), t('User1 has access to group.'));
    $this->assertFalse(og_user_access_entity($perm, 'entity_test', $entity1, $user2), t('User2 does not have access to group.'));

    // User has access to a group associated with a group content.
    $settings = array();
    $settings['type'] = 'article';
    $node = $this->drupalCreateNode($settings);

    $values = array('entity_type' => 'node', 'entity' => $node);
    og_group('entity_test', $entity1->pid, $values);
    $this->assertTrue(og_user_access_entity($perm, 'node', $node, $user1), t('User1 has access to group content.'));
    $this->assertFalse(og_user_access_entity($perm, 'node', $node, $user2), t('User2 does not have access to group content.'));

    // Make group content also a group.
    og_create_field(OG_GROUP_FIELD, 'node', 'article');
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    og_create_field('og_group_ref_2', 'user', 'user', $og_field);

    $settings['uid'] = $user2->uid;
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = 1;
    $node = $this->drupalCreateNode($settings);

    $wrapper = entity_metadata_wrapper('node', $node);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $values = array('entity_type' => 'node', 'entity' => $node);
    og_group('entity_test', $entity1->pid, $values);

    $this->assertTrue(og_user_access_entity($perm, 'node', $node, $user1), t('User1 has access based on access to group.'));
    $this->assertTrue(og_user_access_entity($perm, 'node', $node, $user2), t('User2 has access based on access to group content.'));
    $this->assertFalse(og_user_access_entity($perm, 'node', $node, $user3), t('User3 has no access to entity.'));

    // Entity is a disabled group.
    $settings['uid'] = $user2->uid;
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = 0;
    $node = $this->drupalCreateNode($settings);
    $this->assertNull(og_user_access_entity($perm, 'node', $node, $user1), t('Entity is a disabled group, so return value is NULL.'));

    // Entity is an orphan group content.
    $settings = array();
    $settings['type'] = 'article';
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = 0;
    $node = $this->drupalCreateNode($settings);
    $values = array('entity_type' => 'node', 'entity' => $node);
    og_group('entity_test', $entity1->pid, $values);
    $entity1->delete();
    $this->assertNull(og_user_access_entity($perm, 'node', $node, $user1), t('Entity is an orphan group content, so return value is NULL.'));

    // Entity isn't a group or a group content.
    $settings = array();
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = 0;
    $settings['type'] = 'article';
    $node = $this->drupalCreateNode($settings);
    $this->assertNull(og_user_access_entity($perm, 'node', $node, $user1), t('Entity is not a group or a group contentm, so return value is NULL.'));

    // Entity is NULL - as might be passed by field_access().
    $this->assertNull(og_user_access_entity($perm, 'node', NULL, $user1), t('Entity passed is NULL, so return value is NULL.'));

    // Entity is not saved to database yet.
    unset($node->nid);
    $this->assertNull(og_user_access_entity($perm, 'node', NULL, $user1), t('Entity is not saved to database, so return value is NULL.'));
  }

}
