<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgAudienceFieldAutoCreateTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test group-audience field auto-create.
 *
 * @group og
 */
class OgAudienceFieldAutoCreateTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * Test auto-attaching group-audience fields to the user entity.
   */
  public function testAutoAttach()  {
    $type1 = $this->drupalCreateContentType();
    $type2 = $this->drupalCreateContentType();

    $this->assertFalse(field_info_instance('user', 'og_user_node', 'user'), 'Field does not exist in user entity yet.');
    og_create_field(OG_GROUP_FIELD, 'node', $type1->type);
    $this->assertTrue(field_info_instance('user', 'og_user_node', 'user'), 'Field was added to the user entity.');

    // Change field to reference only type1.
    $field = field_info_field('og_user_node');
    $field['settings']['handler_settings']['target_bundles'] = array($type1->type);
    field_update_field($field);

    // Assert an alternative field name was found.
    $this->assertFalse(field_info_instance('user', 'og_user_node1', 'user'), 'Alternative field does not exist in user entity yet.');
    og_create_field(OG_GROUP_FIELD, 'node', $type2->type);
    $this->assertTrue(field_info_instance('user', 'og_user_node1', 'user'), 'Alternative field was added to the user entity.');
  }

}
