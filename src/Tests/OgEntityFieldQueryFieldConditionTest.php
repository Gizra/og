<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgEntityFieldQueryFieldConditionTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * EntityFieldQuery FieldConditions with multiple group content entity types.
 *
 * @group og
 */
class OgEntityFieldQueryFieldConditionTest extends WebTestBase {

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
    $this->group_type = $type->type;

    $type = $this->drupalCreateContentType();
    $this->group_content_type = $type->type;

    og_create_field(OG_GROUP_FIELD, 'node', $this->group_type);

    // Add audience field to group content node type.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    og_create_field(OG_AUDIENCE_FIELD, 'node', $this->group_content_type, $og_field);
    // Add audience field to group content test entity type.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    og_create_field(OG_AUDIENCE_FIELD, 'entity_test', 'main', $og_field);

    // Create a simple text list field.
    $field = array(
      'entity_types' => array('node'),
      'settings' => array(
        'allowed_values' => array(
          'red' => 'red',
          'blue' => 'blue',
        ),
      ),
      'field_name' => 'list_text',
      'type' => 'list_text',
      'cardinality' => 1,
    );
    $field = field_create_field($field);
    $instance = array(
      'field_name' => 'list_text',
      'bundle' => $this->group_content_type,
      'entity_type' => 'node',
    );
    field_create_instance($instance);

    // Create groups.
    $settings = array(
      'type' => $this->group_type,
      'uid' => $user1->uid,
    );
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = 1;
    $group1 = $this->drupalCreateNode($settings);

    $settings = array(
      'type' => $this->group_type,
      'uid' => $user1->uid,
    );
    $settings[OG_GROUP_FIELD][LANGUAGE_NONE][0]['value'] = 1;
    $group2 = $this->drupalCreateNode($settings);

    // Create group-content.
    // First content node.
    $settings = array(
      'type' => $this->group_content_type,
      'uid' => $user1->uid,
    );
    $settings['list_text'][LANGUAGE_NONE][0]['value'] = 'red';

    $node = $this->drupalCreateNode($settings);

    $values = array(
      'entity_type' => 'node',
      'entity' => $node,
    );
    og_group('node', $group1, $values);

    // Second content node.
    $settings = array(
      'type' => $this->group_content_type,
      'uid' => $user1->uid,
    );
    $settings['list_text'][LANGUAGE_NONE][0]['value'] = 'red';

    $node = $this->drupalCreateNode($settings);

    $values = array(
      'entity_type' => 'node',
      'entity' => $node,
    );
    og_group('node', $group2, $values);

    // Entity test content.
    // We need to create enough test entities so that we get some whose IDs are
    // the same as the group content nodes.
    foreach (range(1, 5) as $i) {
      $entity_test = entity_create('entity_test', array(
        // Weirdly, 'name' is the bundle key.
        'name' => 'main',
        'uid' => $user1->uid,
      ));
      $entity_test->save();
      $values = array(
        'entity_type' => 'entity_test',
        'entity' => $entity_test,
      );
      og_group('node', $group1, $values);
    }

    $this->group1 = $group1;
    $this->group2 = $group2;
  }

  function testEntityFieldQueryFieldConditions() {
    // Query for all nodes with a field value of 'red'.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node');
    $query->entityCondition('bundle', $this->group_content_type);
    $query->fieldCondition('list_text', 'value', 'red');
    $result = $query->execute();

    $this->assertEqual(count($result['node']), 2, "The correct number of nodes was returned for the query with only the plain field.");

    // Query for group content nodes.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node');
    $query->entityCondition('bundle', $this->group_content_type);
    // Condition on the group audience field.
    $query->fieldCondition(OG_AUDIENCE_FIELD, 'target_id', $this->group1->nid);
    $result = $query->execute();

    $this->assertEqual(count($result['node']), 1, "The correct number of nodes was returned for the query with only the OG field condition.");

    // Query for nodes in group 1 with a field value of 'red'.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node');
    $query->entityCondition('bundle', $this->group_content_type);
    // Condition on the group audience field.
    $query->fieldCondition(OG_AUDIENCE_FIELD, 'target_id', $this->group1->nid);
    $query->fieldCondition('list_text', 'value', 'red');
    $result = $query->execute();

    $this->assertEqual(count($result['node']), 1, "The correct number of nodes was returned for the query with the OG field condition first.");

    // Query for nodes in group 1 with a field value of 'red', but this time
    // with the field conditions the other way round.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node');
    $query->entityCondition('bundle', $this->group_content_type);
    $query->fieldCondition('list_text', 'value', 'red');
    // Condition on the group audience field.
    $query->fieldCondition(OG_AUDIENCE_FIELD, 'target_id', $this->group1->nid);
    $result = $query->execute();

    $this->assertEqual(count($result['node']), 1, "The correct number of nodes was returned for the query with the OG field condition second.");
  }

}
