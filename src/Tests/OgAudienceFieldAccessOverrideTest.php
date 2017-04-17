<?php
/**
 * @file
 * Contains \Drupal\og\Tests\OgAudienceFieldAccessOverrideTest.
 */

namespace Drupal\og\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test group-audience field access settings.
 *
 * @group og
 */
class OgAudienceFieldAccessOverrideTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['og'];

  /**
   * Test auto-attaching group-audience fields to the user entity.
   */
  function testAutoAttach()  {
    $type1 = $this->drupalCreateContentType();
    og_create_field(OG_GROUP_FIELD, 'node', $type1->type);
    $this->assertTrue(field_info_instance('user', 'og_user_node', 'user'), 'Field was added to the user entity.');

    // Check that a normal user cannot access this field by default.
    $permissions = array(
      'access content',
      "create $type1->type content",
      'administer group',
    );
    $user1 = $this->drupalCreateUser();
    $this->drupalLogin($user1);
    $this->drupalGet('user/' . $user1->uid . '/edit');
    $this->assertNoRaw('id="edit-og-user-node"');

    // Change field to use entity access instead.
    $instance = field_info_instance('user', 'og_user_node', 'user');
    $instance['settings']['behaviors']['og_widget']['access_override'] = TRUE;
    field_update_instance($instance);

    // The field should now be present.
    $this->drupalGet('user/' . $user1->uid . '/edit');
    $this->assertRaw('id="edit-og-user-node"');
  }

}
