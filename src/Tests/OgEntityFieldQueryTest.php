<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgEntityFieldQueryTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test queying group-audience fields using entityFieldQuery.
 *
 * @group og
 */
class OgEntityFieldQueryTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $user1 = $this->drupalCreateUser();
    $user2 = $this->drupalCreateUser();
    $type = $this->drupalCreateContentType();
    $group_type = $type->type;

    $type = $this->drupalCreateContentType();
    $group_content_type = $type->type;

    og_create_field(OG_GROUP_FIELD, 'node', $group_type);
    og_create_field(OG_GROUP_FIELD, 'entity_test', 'main');

    // Add audience field to reference node.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    og_create_field('og_node', 'node', $group_content_type, $og_field);
    og_create_field('og_node', 'entity_test', 'test', $og_field);

    // Add audience field to reference entity-test.
    $og_field['field']['settings']['target_type'] = 'entity_test';
    og_create_field('og_entity_test', 'node', $group_content_type, $og_field);
    og_create_field('og_entity_test', 'user', 'user', $og_field);


    // Create a non-group audience, entity-refence field.
    $field = array(
      'entity_types' => array('node'),
      'settings' => array(
        'handler' => 'base',
        'target_type' => 'node',
        'handler_settings' => array(
          'target_bundles' => array(),
        ),
      ),
      'field_name' => 'node_reference',
      'type' => 'entityreference',
      'cardinality' => 1,
    );
    $field = field_create_field($field);
    $instance = array(
      'field_name' => 'node_reference',
      'bundle' => $group_content_type,
      'entity_type' => 'node',
    );
    field_create_instance($instance);

    // Create two groups.
    $group1 = entity_create('entity_test', array('name' => 'main', 'uid' => $user1->uid));
    $wrapper = entity_metadata_wrapper('entity_test', $group1);
    $wrapper->{OG_GROUP_FIELD}->set(1);
    $wrapper->save();

    $settings = array(
      'type' => $group_type,
      'uid' => $user1->uid,
    );
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = 1;
    $group2 = $this->drupalCreateNode($settings);

    // Create group-content.
    $settings = array(
      'type' => $group_content_type,
      'uid' => $user1->uid,
    );
    $node = $this->drupalCreateNode($settings);

    $wrapper = entity_metadata_wrapper('node', $node);
    $wrapper->node_reference->set($group2);
    $wrapper->save();

    $values = array(
      'entity_type' => 'node',
      'entity' => $node,
    );

    og_group('entity_test', $group1, $values);
    og_group('node', $group2, $values);

    $entity_test = entity_create('entity_test', array('name' => 'test', 'uid' => $user1->uid));
    $entity_test->save();
    $values = array(
      'entity_type' => 'entity_test',
      'entity' => $entity_test,
    );
    og_group('node', $group2, $values);

    $values = array(
      'entity_type' => 'user',
      'entity' => $user2,
    );
    og_group('node', $group2, $values);

    $this->group1 = $group1;
    $this->group2 = $group2;
    $this->node = $node;
    $this->user1 = $user1;
    $this->user2 = $user2;
    $this->entity_test = $entity_test;
  }

  /**
   * Test the following query scenarios:
   *
   * - Single group audience.
   * - Multiple group audience.
   * - Single group audience first, with another non-audience field.
   * - Non-audience field first, with single group audience.
   * - Multiple entity types in entityCondition().
   * - No entity property.
   * - Non-node entity without revision table (e.g. entity_test).
   * - Non-node entity without revision table and without bundles (e.g. user).
   * - Count query.
   */
  function testEntityFieldQuery()  {
    $group1 = $this->group1;
    $group2 = $this->group2;
    $node = $this->node;
    $user1 = $this->user1;
    $entity_test = $this->entity_test;

    // Single group audience.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('type', $node->type)
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->execute();

    $this->assertEqual(array_keys($result['node']), array($node->nid), 'Single group audience query is correct.');

    // Multiple group audience.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('type', $node->type)
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->fieldCondition('og_entity_test', 'target_id', $group1->pid)
      ->execute();

    $this->assertEqual(array_keys($result['node']), array($node->nid), 'Multiple group audience query is correct.');

    // Single group audience first, with another non-audience field.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('type', $node->type)
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->fieldCondition('node_reference', 'target_id', $group2->nid)
      ->execute();

    $this->assertEqual(array_keys($result['node']), array($node->nid), 'Single group audience first, with another non-audience field query is correct.');

    // Non-audience field first, with single group audience.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('type', $node->type)
      ->fieldCondition('node_reference', 'target_id', $group2->nid)
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->execute();

    $this->assertEqual(array_keys($result['node']), array($node->nid), 'Non-audience field first, with single group audience query is correct.');

    // Multiple entity types in entityCondition().
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', array('node', 'user'), 'IN')
      ->fieldCondition('node_reference', 'target_id', $group2->nid)
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->execute();

    $this->assertEqual(array_keys($result['node']), array($node->nid), 'Multiple entity types in entityCondition() query is correct.');

    // No entity property.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->execute();

    $this->assertEqual(array_keys($result['node']), array($node->nid), 'No entity property query is correct.');

    // Non-node entity without revision table.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'entity_test')
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->execute();

    $this->assertEqual(array_keys($result['entity_test']), array($entity_test->pid), 'Non-node entity without revision table query is correct.');

    // Non-node entity without revision table and without bundles.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'user')
      ->fieldCondition('og_entity_test', 'target_id', $group2->nid)
      ->execute();

    $expected_values = array(
      $this->user1->uid,
      $this->user2->uid,
    );
    $this->assertEqual(array_keys($result['user']), $expected_values, 'Non-node entity without revision table and without bundles query is correct.');

    // Count query.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'node')
      ->propertyCondition('type', $node->type)
      ->fieldCondition('og_node', 'target_id', $group2->nid)
      ->count()
      ->execute();

    $this->assertEqual($result, 1, 'Count query is correct.');
  }

}
