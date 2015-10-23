<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgNodeAccessTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test Group node access. This will test nodes that are groups and group content.
 *
 * @group og
 */
class OgNodeAccessTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp('og');

    // Add OG group field to a the node's "page" bundle.
    og_create_field(OG_GROUP_FIELD, 'node', 'page');

    // Add OG audience field to the node's "article" bundle.
    og_create_field(OG_AUDIENCE_FIELD, 'node', 'article');

    // Create an editor user and a group manager for these tests.
    $this->editor_user = $this->drupalCreateUser(array('access content', 'edit any page content', 'edit any article content', 'create article content'));
    $this->group_manager = $this->drupalCreateUser(array('access content', 'create page content', 'edit own article content', 'edit own page content'));

    // Create group node.
    $settings = array(
      'type' => 'page',
      OG_GROUP_FIELD . '[und][0][value]' => 1,
      'uid' => $this->group_manager->uid
    );
    $this->group1 = $this->drupalCreateNode($settings);
    $this->group2 = $this->drupalCreateNode($settings);

    // Create node to add to group.
    $settings = array(
      'type' => 'article',
      'uid' => $this->group_manager->uid,
    );
    $this->group_content = $this->drupalCreateNode($settings);

    // Add node to group.
    $values = array(
      'entity_type' => 'node',
      'entity' => $this->group_content,
    );
    og_group('node', $this->group1, $values);
  }

  /**
   * Test strict access permissions for updating group node. A non-member of
   * a group who has core node access update permission is denied access.
   */
  function testStrictAccessNodeUpdate() {
    // Set Node access strict variable.
    variable_set('og_node_access_strict', TRUE);

    // Login as editor and try to change the group node and group content.
    $this->drupalLogin($this->editor_user);

    $this->drupalGet('node/' . $this->group1->nid . '/edit');
    $this->assertResponse('403', t('A non-member with core node access permissions was denied access to edit group node.'));

    $this->drupalGet('node/' . $this->group_content->nid . '/edit');
    $this->assertResponse('403', t('A non-member with core node access permissions was denied access to edit group content node.'));

    // Login as a group manager and try to change group node.
    $this->drupalLogin($this->group_manager);

    $this->drupalGet('node/' . $this->group1->nid . '/edit');
    $this->assertResponse('200', t('Group manager allowed to access to edit group node.'));

    $this->drupalGet('node/' . $this->group_content->nid . '/edit');
    $this->assertResponse('200', t('Group manager allowed to access to edit group content node.'));
  }

  /**
   * Test access to node create on strict mode.
   */
  function testStrictAccessNodeCreate() {
    // Set Node access strict variable.
    variable_set('og_node_access_strict', TRUE);
    $editor_user = $this->editor_user;
    $this->drupalLogin($editor_user);

    $this->drupalGet('node/add/article');
    $this->assertResponse('200', t('User can access node create with non-required field.'));

    $instance = field_info_instance('node', OG_AUDIENCE_FIELD, 'article');
    $instance['required'] = TRUE;
    field_update_instance($instance);

    $this->drupalGet('node/add/article');
    $this->assertResponse('403', t('User cannot access node create with required field.'));

    // Test OG's create permission for a group member.
    $editor_user = user_load($editor_user->uid);
    og_group('node', $this->group1, array('entity' => $editor_user));
    $roles = array_flip(og_roles('node', 'page'));

    $permissions = array(
      'create article content' => 0,
      'update own article content' => 1,
      'update any article content' => 1,
    );

    // Add update permission.
    og_role_change_permissions($roles[OG_AUTHENTICATED_ROLE], $permissions);
    $this->drupalGet('node/add/article');
    $this->assertResponse('403', 'Group member cannot create node.');

    // Add create permission.
    $permissions = array(
      'create article content' => 1,
      'update own article content' => 0,
      'update any article content' => 0,
    );
    og_role_change_permissions($roles[OG_AUTHENTICATED_ROLE], $permissions);
    $this->drupalGet('node/add/article');
    $this->assertResponse('200', 'Group member can create node.');
  }

  /**
   * Test non-strict access permissions for updating group node. A non-member
   * of a group who has core node access update permission is allowed access.
   */
  function testNoStrictAccessNodeUpdate() {
    // Set Node access strict variable.
    variable_set('og_node_access_strict', FALSE);

    // Login as editor and try to change the group node and group content.
    $this->drupalLogin($this->editor_user);

    $this->drupalGet('node/' . $this->group1->nid . '/edit');
    $this->assertResponse('200', t('A non-member with core node access permissions was not denied access.'));

    $this->drupalGet('node/' . $this->group_content->nid . '/edit');
    $this->assertResponse('200', t('A non-member with core node access permissions was not denied access to edit group content node.'));

    // Login as a group manager and try to change group node.
    $this->drupalLogin($this->group_manager);

    $this->drupalGet('node/' . $this->group1->nid . '/edit');
    $this->assertResponse('200', t('Group manager allowed to access to edit group node.'));

    $this->drupalGet('node/' . $this->group_content->nid . '/edit');
    $this->assertResponse('200', t('Group manager allowed to access to edit group content node.'));
  }

  /**
   * Test non-strict access permissions for creating group node.
   *
   * A member of a group who has no core node access create permission is
   * allowed access.
   */
  function testNoStrictAccessNodeCreate() {
    // Set Node access strict variable.
    variable_set('og_node_access_strict', FALSE);

    $this->group_editor_user = $this->drupalCreateUser(array('access content'));
    $this->drupalLogin($this->group_editor_user);

    // Test OG's create permission for a group member.
    og_group('node', $this->group1, array('entity' => $this->group_editor_user));
    $roles = array_flip(og_roles('node', 'page'));

    // Add create permission.
    $permissions = array(
      'create article content' => 1,
      'update own article content' => 0,
      'update any article content' => 0,
    );
    og_role_change_permissions($roles[OG_AUTHENTICATED_ROLE], $permissions);
    $this->drupalGet('node/add/article');
    $this->assertResponse('200', 'Group member can create node.');
  }

  /**
   * Assert a user cannot assign an existing node to a group they don't
   * have "create" permissions.
   */
  function testNodeUpdateAudienceField() {
    // Set Node access strict variable.
    variable_set('og_node_access_strict', TRUE);
    $editor_user = $this->editor_user;

    // Add editor to a single groups.
    og_group('node', $this->group1, array('entity' => $editor_user));
    og_group('node', $this->group2, array('entity' => $editor_user));

    // Add group-content to a single group.
    og_group('node', $this->group1, array('entity_type' => 'node', 'entity' => $this->group_content));

    // Allow member to update and create.
    $og_roles = array_flip(og_roles('node', 'page'));
    $permissions = array(
      'create article content' => 1,
      'update any article content' => 1,
    );
    og_role_change_permissions($og_roles[OG_AUTHENTICATED_ROLE], $permissions);

    // Login and try to edit this node
    $this->drupalLogin($this->editor_user);

    $this->drupalGet('node/'. $this->group_content->nid .'/edit');
    $name = 'og_group_ref[und][0][default][]';
    $xpath = $this->buildXPathQuery('//select[@name=:name]', array(':name' => $name));
    $fields = $this->xpath($xpath);
    $this->assertTrue(!empty($fields[0]->option[2]), 'User can assign group-content to a new group.');

    // Allow member to update but not create.
    $og_roles = array_flip(og_roles('node', 'page'));
    $permissions = array(
      'create article content' => 0,
      'update any article content' => 1,
    );
    og_role_change_permissions($og_roles[OG_AUTHENTICATED_ROLE], $permissions);

    $this->drupalGet('node/'. $this->group_content->nid .'/edit');
    $xpath = $this->buildXPathQuery('//select[@name=:name]', array(':name' => $name));
    $fields = $this->xpath($xpath);
    $this->assertFalse(!empty($fields[0]->option[2]), 'User cannot assign group-content to a new group.');

    // Test for retaining groups on node save.
    $this->drupalPost('node/'. $this->group_content->nid .'/edit', array(), t('Save'));

    $entity_groups = og_get_entity_groups('node', $this->group_content->nid);
    $this->assertFalse(in_array($this->group2->nid, $entity_groups['node']), 'Content retains original groups after saving node form.');
  }

}
