<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgUserCanPublishGroupContentTypeOnlyInGroupTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verify that users only with OG permissions can post only inside a group
 *
 * @group og
 */
class OgUserCanPublishGroupContentTypeOnlyInGroupTest extends WebTestBase {
  public $group;
  public $site_user;
  public $group_user;
  public $group_content;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Create a group content type.
    $group = $this->drupalCreateContentType();
    og_create_field(OG_GROUP_FIELD, 'node', $group->type);

    // Create the group.
    $settings = array(
      'type' => $group->type,
      OG_GROUP_FIELD . '[und][0][value]' => 1,
      'uid' => 1,
    );
    $this->group = $this->drupalCreateNode($settings);

    // Create the group content content type.
    $this->group_content = $this->drupalCreateContentType();

    // Attach the audience field.
    og_create_field(OG_AUDIENCE_FIELD, 'node', $this->group_content->type);

    // Add permission to the group.
    $og_roles = og_roles('node', $group->type);
    $rid = array_search(OG_AUTHENTICATED_ROLE, $og_roles);
    og_role_change_permissions($rid, array(
      'create ' . $this->group_content->type . ' content' => 1,
      'update own ' . $this->group_content->type . ' content' => 1,
    ));

    // Creating users.
    $this->group_user = $this->drupalCreateUser();
    $this->site_user = $this->drupalCreateUser(array('create ' . $this->group_content->type . ' content', 'edit own ' . $this->group_content->type . ' content'));

    og_group('node', $this->group->nid, array('entity' => $this->group_user));
  }

  /**
   * Grant to a user the permission to publish a node of a group content and
   * verify that he can't create a node of that content type outside a group.
   */
  public function testGroupUserCanPostGroupContentOnlyInGroup() {
    $node_title = $this->randomName();
    $this->drupalLogin($this->group_user);
    $this->drupalPost('node/add', array('title' => $node_title), t('Save'));
    $this->assertText("You must select one or more groups for this content", "The user can't publish a content outside a group");

    // Check the user can publish node inside the group.
    $edit = array(
      'title' => $node_title,
      'og_group_ref[und][0][default][]' => array($this->group->nid),
    );
    $this->drupalPost('node/add', $edit, t('Save'));

    $this->assertText($this->group_content->type . " " . $node_title . " has been created.", "The user can create content inside a group.");

    // Check the user can edit the node.
    $query = new entityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('title', $node_title)
      ->fieldCondition(OG_AUDIENCE_FIELD, 'target_id', $this->group->nid)
      ->execute();
    $node_title = $this->randomName();

    $edit = array(
      'title' => $node_title,
      'og_group_ref[und][0][default][]' => array(),
    );
    $this->drupalPost('node/' . reset($result['node'])->nid . '/edit', $edit, t('Save'));
    $this->assertText("You must select one or more groups for this content", "The user can't edit a content outside a group");

    $edit = array(
      'title' => $node_title,
      'og_group_ref[und][0][default][]' => array($this->group->nid),
    );
    $this->drupalPost('node/' . reset($result['node'])->nid . '/edit', $edit, t('Save'));
    $this->assertText($this->group_content->type . " " . $node_title . " has been updated.", "The user can edit content inside a group.");
  }

  /**
   * Verify that non-group user can post group content outside of a group.
   */
  public function testNonGroupUserCanPostGroupContentOutsideGroup() {
    $this->drupalLogin($this->site_user);

    // Set node access strict variable to FALSE for posting outside groups.
    variable_set('og_node_access_strict', FALSE);

    // Verify that the user can publish group content outside a group.
    $node_title = $this->randomName();
    $this->drupalPost('node/add', array('title' => $node_title), t('Save'));

    $params = array(
      '@type' => $this->group_content->type,
      '@title' => $node_title,
    );
    $this->assertText(format_string("@type @title has been created.", $params), "The user can create content outside a group.");

    // Check the user can edit the node.
    $query = new entityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('title', $node_title)
      ->execute();

    $node_title = $this->randomName();
    $edit = array(
      'title' => $node_title,
    );
    $this->drupalPost('node/' . reset($result['node'])->nid . '/edit', $edit, t('Save'));
    $this->assertText($this->group_content->type . " " . $node_title . " has been updated.", "The user can edit content outside a group.");
  }

}
