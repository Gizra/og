<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgDeleteOrphansTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for deleting orphans group content.
 *
 * @group og
 */
class OgDeleteOrphansTest extends WebTestBase {

  protected $group_type;
  protected $node_type;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['og', 'og_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Create a group content type.
    $group = $this->drupalCreateContentType();
    og_create_field(OG_GROUP_FIELD, 'node', $group->type);
    $this->group_type = $group->type;

    // Create group audience content type.
    $type = $this->drupalCreateContentType();
    $this->node_type = $type->type;

    // Add OG audience field to the audience content type.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'node';
    og_create_field(OG_AUDIENCE_FIELD, 'node', $type->type, $og_field);

    // Set the setting for delete a group content when deleting group.
    variable_set('og_orphans_delete', TRUE);
    variable_set('og_use_queue', TRUE);
  }

  /**
   * Tests two things:
   *  When deleting a group, the node of the group will be deleted.
   *  Associated node with the deleted group and another group won't be deleted.
   */
  function testDeleteGroup() {
    // Creating two groups.
    $first_group = $this->drupalCreateNode(array('type' => $this->group_type));
    $second_group = $this->drupalCreateNode(array('type' => $this->group_type));

    // Create two nodes.
    $first_node = $this->drupalCreateNode(array('type' => $this->node_type));
    og_group('node', $first_group, array('entity_type' => 'node', 'entity' => $first_node));
    og_group('node', $second_group, array('entity_type' => 'node', 'entity' => $first_node));

    $second_node = $this->drupalCreateNode(array('type' => $this->node_type));
    og_group('node', $first_group, array('entity_type' => 'node', 'entity' => $second_node));

    // Delete the group.
    node_delete($first_group->nid);

    // Execute manually the queue worker.
    $queue = DrupalQueue::get('og_membership_orphans');
    $item = $queue->claimItem();
    og_membership_orphans_worker($item->data);

    // Load the nodes we used during the test.
    $first_node = node_load($first_node->nid);
    $second_node = node_load($second_node->nid);

    // Verify the none orphan node wasn't deleted.
    $this->assertTrue($first_node, "The second node is realted to another group and deleted.");
    // Verify the orphan node deleted.
    $this->assertFalse($second_node, "The orphan node deleted.");
  }

  /**
   * Tests the moving of the node to another group when deleting a group.
   */
  function testMoveOrphans() {
    // Creating two groups.
    $first_group = $this->drupalCreateNode(array('type' => $this->group_type, 'title' => 'move'));
    $second_group = $this->drupalCreateNode(array('type' => $this->group_type));

    // Create a group and relate it to the first group.
    $first_node = $this->drupalCreateNode(array('type' => $this->node_type));
    og_group('node', $first_group, array('entity_type' => 'node', 'entity' => $first_node));

    // Delete the group.
    node_delete($first_group->nid);

    // Execute manually the queue worker.
    $queue = DrupalQueue::get('og_membership_orphans');
    $item = $queue->claimItem();
    og_membership_orphans_worker($item->data);

    // Load the node into a wrapper and verify we moved him to another group.
    $gids = og_get_entity_groups('node', $first_node->nid);
    $gid = reset($gids['node']);

    $this->assertEqual($gid, $second_group->nid, 'The group content moved to another group.');
  }

}
