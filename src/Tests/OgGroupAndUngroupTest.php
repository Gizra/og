<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgGroupAndUngroupTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test Group content handling.
 *
 * @group og
 */
class OgGroupAndUngroupTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    // Add OG group field to the entity_test's "main" bundle.
    og_create_field(OG_GROUP_FIELD, 'entity_test', 'main');

    // Add OG audience field to the node's "article" bundle.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'entity_test';
    og_create_field(OG_AUDIENCE_FIELD, 'node', 'article', $og_field);
  }

  /**
   * Test group and ungroup of content.
   */
  function testGroupAndUngroup() {
    $user1 = $this->drupalCreateUser();
    $user2 = $this->drupalCreateUser();

    $entity1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $entity2 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity2);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $entity3 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity3);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $settings = array();
    $settings['type'] = 'article';
    $settings['uid'] = $user2->uid;
    $node = $this->drupalCreateNode($settings);

    // Exception on OG membership for anonymous user.
    try {
      og_membership_create('entity_test', $entity1->pid, 'user', 0, OG_AUDIENCE_FIELD)->save();
      $this->fail('OG membership can be created for anonymous user.');
    }
    catch (OgException $e) {
      $this->pass('OG membership can not be created for anonymous user.');
    }

    $this->assertFalse(og_is_member('entity_test', $entity1->pid, 'node', $node), t('Node is not assigned to group1.'));
    $values = array('entity_type' => 'node', 'entity' => $node);
    og_group('entity_test', $entity1->pid, $values);
    $og_membership = og_get_membership('entity_test', $entity1->pid, 'node', $node->nid);
    $id = $og_membership->id;
    $this->assertTrue(og_is_member('entity_test', $entity1->pid, 'node', $node), t('Node is assigned to group1 with active state.'));

    // State changed.
    $values += array('state' => OG_STATE_BLOCKED);
    og_group('entity_test', $entity1->pid, $values);
    $og_membership = og_get_membership('entity_test', $entity1->pid, 'node', $node->nid);
    $this->assertEqual($id, $og_membership->id, t('OG membership was updated.'));
    $this->assertTrue(og_is_member('entity_test', $entity1->pid, 'node', $node, array(OG_STATE_BLOCKED)), t('Node is assigned to group1 with blocked state.'));

    // Exception on existing OG membership.
    try {
      og_membership_create('entity_test', $entity1->pid, 'node', $node->nid, OG_AUDIENCE_FIELD)->save();
      $this->fail('Saving multiple OG membership for same entity and group works.');
    }
    catch (OgException $e) {
      $this->pass('Saving multiple OG membership for same entity and group does not work.');
    }

    // Add a second audience field.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'entity_test';
    $og_field['field']['cardinality'] = 2;
    og_create_field('og_ref_2', 'node', 'article', $og_field);

    // Re-group to another field.
    $values += array('field_name' => 'og_ref_2');
    og_group('entity_test', $entity1->pid, $values);
    $og_membership = og_get_membership('entity_test', $entity1->pid, 'node', $node->nid);
    $this->assertNotEqual($id, $og_membership->id, t('OG membership was re-created.'));
    $this->assertEqual('og_ref_2', $og_membership->field_name, t('OG membership is registered under correct field.'));


    // Exception on field cardinality.
    og_group('entity_test', $entity2->pid, $values);
    try {
      og_group('entity_test', $entity3->pid, $values);
      $this->fail('Grouping beyond field cardinality works.');
    }
    catch (OgException $e) {
      $this->pass('Grouping beyond field cardinality does not work.');
    }

    // Exception as field-name is incorrect.
    $values['field_name'] = 'wrong-field-name';
    try {
      og_group('entity_test', $entity1->pid, $values);
      $this->fail('Grouping with incorrect field name works.');
    }
    catch (OgException $e) {
      $this->pass('Grouping with incorrect field name does not work.');
    }

    // Exception on audience field, referencing wrong target type.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'node';
    og_create_field('og_ref_3', 'node', 'article', $og_field);
    $values['field_name'] = 'og_ref_3';
    try {
      og_group('entity_test', $entity1->pid, $values);
      $this->fail('Grouping with wrong target type works.');
    }
    catch (OgException $e) {
      $this->pass('Grouping with wrong target type does not work.');
    }

    // Exception on audience field, referencing correct target type, but wrong
    // bundles.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'entity_test';
    $og_field['field']['settings']['handler_settings']['target_bundles'] = array('test');
    og_create_field('og_ref_4', 'node', 'article', $og_field);
    $values['field_name'] = 'og_ref_4';
    try {
      og_group('entity_test', $entity1->pid, $values);
      $this->fail('Grouping with wrong target bundles works.');
    }
    catch (OgException $e) {
      $this->pass('Grouping with wrong target bundles does not work.');
    }


    // Exception as user has no group-audience field.
    $instance = field_info_instance('user', 'og_user_entity_test', 'user');
    field_delete_instance($instance);

    try {
      $entity2 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
      $wrapper = entity_metadata_wrapper('entity_test', $entity2);
      $wrapper->{OG_GROUP_FIELD}->set(1);
      $wrapper->save();
      $this->fail('Grouping with no group-audience field in bundle works.');
    }
    catch (OgException $e) {
      $this->pass('Grouping with no group-audience field in bundle does not work.');
    }

    // Ungroup node from group.
    og_ungroup('entity_test', $entity1->pid, 'node', $node);
    $og_membership = og_get_membership('entity_test', $entity1->pid, 'node', $node->nid);
    $this->assertFalse($og_membership, t('Node was ungrouped from group.'));

    // Delete node and confirm memberships were deleted.
    $values = array('entity_type' => 'node', 'entity' => $node);
    og_group('entity_test', $entity1->pid, $values);
    $nid = $node->nid;

    // Re-load node, to make sure we are deleting the most up-to-date one,
    // after it was altered by og_group().
    $node = node_load($nid, NULL, TRUE);
    node_delete($nid);
    $this->assertFalse(og_get_entity_groups('node', $nid), t('OG memberships deleted on entity deletion.'));

    // Test creating a non-saved OG membership.
    $settings = array();
    $settings['type'] = 'article';
    $settings['uid'] = $user2->uid;
    $node = $this->drupalCreateNode($settings);
    $values = array('entity_type' => 'node', 'entity' => $node);

    $og_membership = og_group('entity_test', $entity1->pid, $values);
    $this->assertTrue($og_membership->id, 'New OG membership was created and saved.');

    $settings = array();
    $settings['type'] = 'article';
    $settings['uid'] = $user2->uid;
    $node = $this->drupalCreateNode($settings);
    $values = array('entity_type' => 'node', 'entity' => $node);

    $og_membership = og_group('entity_test', $entity1->pid, $values, FALSE);
    $this->assertTrue(empty($og_membership->id), 'New OG membership was created but not saved.');
  }

  /**
   * Test granting deault role to group manager.
   */
  function testGroupManagerDefaultRoles() {
    // Get only the admin role.
    $og_roles = og_roles('entity_test', 'main', 0, FALSE, FALSE);
    variable_set('og_group_manager_default_rids_entity_test_main', array_keys($og_roles));
    $user1 = $this->drupalCreateUser();

    $entity1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $user_roles = og_get_user_roles('entity_test', $entity1->pid, $user1->uid, FALSE);
    $this->assertEqual($og_roles, $user_roles, t('Group manager was granted default role.'));
  }

}
