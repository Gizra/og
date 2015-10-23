<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgAutoCompleteAccessibleGroupsValidationTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test for publishing content into group the user is not a member.
 *
 * @group og
 */
class OgAutoCompleteAccessibleGroupsValidationTest extends WebTestBase {

  var $dummy_content_type;

  var $group_node_1;
  var $group_node_2;

  var $group_owner;
  var $group_member;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Create group content.
    $this->drupalCreateContentType(array( 'name' => 'Group content', 'type' => 'group_content'));
    $this->drupalCreateContentType(array( 'name' => 'Group', 'type' => 'group'));

    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['instance']['settings']['behaviors']['og_widget']['default']['widget_type'] = 'entityreference_autocomplete';
    og_create_field(OG_AUDIENCE_FIELD, 'node', 'group_content', $og_field);
    og_create_field(OG_GROUP_FIELD, 'node', 'group');

    // Create users.
    $this->group_owner = $this->drupalCreateUser(array('create group_content content', 'administer group'));
    $this->group_member = $this->drupalCreateUser(array('create group_content content'));

    // Create a group node.
    $settings = array();
    $settings['type'] = 'group';
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = TRUE;
    $settings['uid'] = $this->group_owner->uid;
    $this->group_node_1 = $this->drupalCreateNode($settings);
    og_group('node', $this->group_node_1, array(
      'entity' => $this->group_member,
    ));

    $settings = array();
    $settings['type'] = 'group';
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = TRUE;
    $settings['uid'] = $this->drupalCreateUser()->uid;
    $this->group_node_2 = $this->drupalCreateNode($settings);
  }

  /**
   * Verify that a user can't publish content into group that he isn't a member.
   */
  function testAuthenticatedUserCantReferenceToPrivateGroup() {
    $this->drupalLogin($this->group_member);

    // Try to publish content into group the user is not a member.
    $this->drupalPost('node/add/group_content', array('title' => 'New group content', 'og_group_ref[und][0][default][0][target_id]' => $this->group_node_2->title . '(' . $this->group_node_2->nid . ')'), 'Save');
    $this->assertText('The referenced group (node: ' . $this->group_node_2->nid . ') is invalid.', 'The reference to group the user is not accessible has achieved');

    // Try to publish content into group that doesn't exist.
    $this->drupalPost('node/add/group_content', array('title' => 'New group content', 'og_group_ref[und][0][default][0][target_id]' => 'foo (900)'), 'Save');
    $this->assertText('The referenced group (node: 900) is invalid.', 'The reference to group the user is not accessible has achieved');

    // Try to publish content into the group the user is a member of.
    $this->drupalPost('node/add/group_content', array('title' => 'New group content 2', 'og_group_ref[und][0][default][0][target_id]' => $this->group_node_1->title . '(' . $this->group_node_1->nid . ')'), 'Save');
    $this->assertText('Group content New group content 2 has been created.', 'The group content created successfully');

    // Testing the widgets my group and other groups.
    $this->drupalLogin($this->group_owner);

    $this->drupalPost('node/add/group_content', array('title' => 'New group content 3', 'og_group_ref[und][0][default][0][target_id]' => $this->group_node_2->title . '(' . $this->group_node_2->nid . ')'), 'Save');
    $this->assertText('The referenced group (node: ' . $this->group_node_2->nid . ') is invalid.', 'The reference to group the user is not accessible has achieved');

    $this->drupalPost('node/add/group_content', array('title' => 'New group content 3', 'og_group_ref[und][0][admin][0][target_id]' => $this->group_node_2->title . '(' . $this->group_node_2->nid . ')'), 'Save');
    $this->assertText('Group content New group content 3 has been created.', 'The group content created successfully');

    $this->drupalPost('node/add/group_content', array('title' => 'New group content 3', 'og_group_ref[und][0][admin][0][target_id]' => 'foo (900)'), 'Save');
    $this->assertText('The referenced group (node: 900) is invalid.', 'The reference to group the user is not accessible has achieved');

    // Try to publish content into the group the user is a member of.
    $this->drupalPost('node/add/group_content', array('title' => 'New group content 4', 'og_group_ref[und][0][default][0][target_id]' => $this->group_node_1->title . '(' . $this->group_node_1->nid . ')'), 'Save');
    $this->assertText('Group content New group content 4 has been created.', 'The group content created successfully');
  }

}
