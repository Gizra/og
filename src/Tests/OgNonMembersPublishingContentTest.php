<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgNonMembersPublishingContentTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Testing publishing content in a group for non members users.
 *
 * @group og
 */
class OgNonMembersPublishingContentTest extends WebTestBase {

  public $group;
  public $user;
  public $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og', 'entityreference_prepopulate'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    // Creating a user.
    $this->user = $this->drupalCreateUser();

    // Creating admin.
    $this->adminUser = $this->drupalCreateUser(array('bypass node access', 'administer group'));

    // Create a group content type.
    $this->drupalCreateContentType(array( 'name' => 'Group', 'type' => 'group'));
    og_create_field(OG_GROUP_FIELD, 'node', 'group');

    // Create the group.
    $settings = array(
      'type' => 'group',
      OG_GROUP_FIELD . '[und][0][value]' => 1,
      'uid' => 1,
    );
    $this->group = $this->drupalCreateNode($settings);

    // Create the group content.
    $this->drupalCreateContentType(array('name' => 'Group content', 'type' => 'group_content'));

    // Attach the audience field and enable the prepopulate behavior.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['instance']['settings']['behaviors']['prepopulate'] = array(
      'status' => TRUE,
      'action' => 'none',
    );
    og_create_field(OG_AUDIENCE_FIELD, 'node', 'group_content', $og_field);

    // Add permission to the group.
    $og_roles = og_roles('node', 'group');
    $rid = array_search(OG_ANONYMOUS_ROLE, $og_roles);
    og_role_change_permissions($rid, array('create group_content content' => 1));
  }

  /**
   * Testing the option for non members users to publish content in the group
   * when the group identifier passed through the URL.
   */
  public function testNonMembersPublish() {
    // Login as normal user.
    $this->drupalLogin($this->user);

    // Verify the user can't publish content to the group content.
    $this->drupalGet('node/add/group-content');
    $this->assertResponse('403', t('The user can not create post in the group.'));

    // Create a the group content.
    $this->drupalPost('node/add/group-content', array('title' => 'foo'), t('Save'), array('query' => array('og_group_ref' => $this->group->nid)));

    // Verify the node belong to the group.
    $query = new entityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('title', 'foo')
      ->fieldCondition(OG_AUDIENCE_FIELD, 'target_id', $this->group->nid)
      ->execute();

    $this->assertTrue(!empty($result['node']), 'The node was added to the group.');

    $nid = reset(array_keys($result['node']));

    $this->drupalLogin($this->adminUser);

    // Verify the audience field will remain after another user editing the
    // group.
    $this->drupalpost('node/' . $nid . '/edit', array(), t('Save'));
    $this->assertText($this->group->title, 'The node is still referenced to the group.');
  }

}
