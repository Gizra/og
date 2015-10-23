<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgMetaDataTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the Organic groups API and CRUD handling.
 *
 * @group og
 */
class OgMetaDataTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * Test the og_get_entity_groups() API function.
   */
  function testOgMembershipMetaData() {
    // Add OG group field to the entity_test's "main" bundle.
    og_create_field(OG_GROUP_FIELD, 'entity_test', 'main');

    // Add OG audience field to the node's "article" bundle.
    $og_field = og_fields_info(OG_AUDIENCE_FIELD);
    $og_field['field']['settings']['target_type'] = 'entity_test';
    og_create_field(OG_AUDIENCE_FIELD, 'node', 'article', $og_field);

    // Add a second audience field.
    og_create_field('og_ref_2', 'node', 'article', $og_field);

    $user1 = $this->drupalCreateUser();

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

    // Create group entities.
    foreach (og_group_content_states() as $state => $value) {
      $node = $this->drupalCreateNode($settings);

      // Assign article to the group.
      $values = array('entity_type' => 'node', 'entity' => $node);
      og_group('entity_test', $entity1->pid, $values + array('state' => $state));
      // Subscribe node to a second group, but with a different state, by
      // selecting the state code and incrementing by one (e.g. is the
      // state is "active" then the other-state will be "pending").
      $other_state = $state == OG_STATE_BLOCKED ? OG_STATE_ACTIVE : $state + 1;
      $values += array('state' => $other_state);
      og_group('entity_test', $entity2->pid, $values);

      // Subscribe node to third group, using a different field.
      $values += array('field_name' => 'og_ref_2');
      og_group('entity_test', $entity3->pid, $values);

      $wrapper = entity_metadata_wrapper('node', $node->nid);
      $this->assertEqual($wrapper->og_membership->count(), 3, t('Found all OG memberships.'));

      $og_memberships = $wrapper->{'og_membership__' . $state}->value();
      $this->assertEqual(count($og_memberships), 1, t('Found 1 OG membership with state @state.', array('@state' => $value)));
      $this->assertEqual($og_memberships[0]->state, $state, t('OG membership has correct @state state.', array('@state' => $value)));

      $og_memberships = $wrapper->{OG_AUDIENCE_FIELD . '__og_membership__' . $state}->value();
      $this->assertEqual(count($og_memberships), 1, t('Found 1 OG membership with state @state in group-audience field.', array('@state' => $value)));
      $this->assertEqual($og_memberships[0]->field_name, OG_AUDIENCE_FIELD, t('OG membership with state @state is referencing correct field name in group-audience field.', array('@state' => $value)));
    }

    $og_memberships = $wrapper->{OG_AUDIENCE_FIELD . '__og_membership'}->value();
    $this->assertEqual(count($og_memberships), 2, t('Found 2 OG membership in group-audience field.', array('@state' => $value)));
    $this->assertEqual($og_memberships[0]->field_name, OG_AUDIENCE_FIELD, t('OG membership has correct group-audience field.'));

    $og_memberships = $wrapper->{'og_ref_2__og_membership'}->value();
    $this->assertEqual(count($og_memberships), 1, t('Found 2 OG membership in second group-audience field.', array('@state' => $value)));
    $this->assertEqual($og_memberships[0]->field_name, 'og_ref_2', t('OG membership has correct group-audience field.'));
  }

}
