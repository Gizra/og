<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgBehaviorHandlerTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the OG Behavior Handler.
 *
 * @group og
 */
class OgBehaviorHandlerTest  extends WebTestBase {

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

    $type = $this->drupalCreateContentType(array('type' => 'behavior'));
    $this->group_content = $type->type;

    // Add OG audience field to the new bundle.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'entity_test';
    og_create_field(OG_AUDIENCE_FIELD, 'node', $type->type, $og_field);
  }

  /**
   * Test piping group association via the group-audience field.
   */
  function testGroupAudienceField() {
    $user1 = $this->drupalCreateUser();
    $user2 = $this->drupalCreateUser();

    $entity1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $settings = array();
    $settings['type'] = $this->group_content;
    $settings['uid'] = $user2->uid;
    $node = $this->drupalCreateNode($settings);

    $wrapper = entity_metadata_wrapper('node', $node);

    $this->assertFalse(og_is_member('entity_test', $entity1->pid, 'node', $node), t('Node is not assigned to group1.'));
    $wrapper->{OG_AUDIENCE_FIELD}[] = $entity1->pid;
    $wrapper->save();
    $og_membership = og_get_membership('entity_test', $entity1->pid, 'node', $node->nid);
    $id = $og_membership->id;
    $this->assertTrue(og_is_member('entity_test', $entity1->pid, 'node', $node), t('Node is assigned to group1 with active state.'));

    $wrapper->{OG_AUDIENCE_FIELD}->set(NULL);
    $wrapper->save();
    $this->assertFalse(og_get_entity_groups('node', $node), t('Node is not associated with any group.'));
  }

  /**
   * Test skipping OgBehaviorHandler.
   */
  function testGroupAudienceFieldSkipBehavior() {
    $user1 = $this->drupalCreateUser();
    $user2 = $this->drupalCreateUser();

    $entity1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $settings = array();
    $settings['type'] = $this->group_content;
    $settings['uid'] = $user2->uid;
    $node = $this->drupalCreateNode($settings);

    og_group('entity_test', $entity1, array('entity_type' => 'node', 'entity' => $node));
    $node->og_group_on_save = array('group_type' => 'entity_test', 'gid' => $entity1->pid);
    node_save($node);

    $this->assertFalse(og_get_entity_groups('node', $node), 'Widget behavior removed group association as expected.');

    $node = node_load($node->nid);
    $node->og_group_on_save = array('group_type' => 'entity_test', 'gid' => $entity1->pid);
    $node->skip_og_membership = TRUE;
    node_save($node);

    $gids = og_get_entity_groups('node', $node);
    $this->assertEqual(array_values($gids['entity_test']), array($entity1->pid), 'Widget behavior was skipped and removed group association as expected.');
  }

  /**
   * Test settings the OG membership state via field values, when associating
   * a new group-content to a group.
   */
  function testSetStateOnInsert() {
    module_enable(array('og_test'));
    $permissions = array(
      'access content',
      "create $this->group_content content",
      'administer group',
    );
    $user1 = $this->drupalCreateUser();
    $user2 = $this->drupalCreateUser($permissions);
    $user3 = $this->drupalCreateUser($permissions);

    // Create a group.
    $entity1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $entity1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    og_group('entity_test', $entity1, array('entity_type' => 'user', 'entity' => $user2));
    og_group('entity_test', $entity1, array('entity_type' => 'user', 'entity' => $user3));

    // Post a node, state should be active.
    $type = str_replace('_', '-', $this->group_content);
    $edit = array(
      'title' => 'state-active',
      'og_group_ref[und][0][default][]' => array($entity1->pid),
    );

    $this->drupalLogin($user2);
    $this->drupalPost('node/add/' . $type, $edit, t('Save'));

    $gids = og_get_entity_groups('node', 1);
    $id = key($gids['entity_test']);
    $og_membership = og_membership_load($id);
    $this->assertEqual($og_membership->state, OG_STATE_ACTIVE, 'Memebership status is Active');


    // Post a node, state should be pending.
    $this->drupalLogin($user3);
    $edit['title'] = 'state-pending';
    $this->drupalPost('node/add/' . $type, $edit, t('Save'));
    $gids = og_get_entity_groups('node', 2, array(OG_STATE_PENDING));
    $id = key($gids['entity_test']);
    $og_membership = og_membership_load($id);
    $this->assertEqual($og_membership->state, OG_STATE_PENDING, 'Memebership status is Active');
  }

}
