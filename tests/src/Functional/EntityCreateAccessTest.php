<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Functional\EntityCreateAccessTest.
 */

namespace Drupal\Tests\og\Functional;

use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\simpletest\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Tests access to the create entity form through the user interface.
 *
 * @see og_entity_create_access().
 *
 * @group og
 */
class EntityCreateAccessTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Create a "group" node type and turn it into a group type.
    $this->createContentType(['type' => 'group']);
    Og::groupManager()->addGroup('node', 'group');

    // Add a group audience field to the "post" node type, turning it into a
    // group content type.
    $this->createContentType(['type' => 'post']);
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'post');
  }

  /**
   * Tests that users that can only view cannot access the entity creation form.
   *
   * @see https://github.com/amitaibu/og/pull/166
   */
  function testViewPermissionDoesNotGrantCreateAccess() {
    // Create a group content type.
    $settings = ['type' => 'group'];
    $this->createNode($settings);

    // Create a user that only has permission to view published content.
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Verify that the user does not have access to the entity create form of
    // the group content type.
    $this->drupalGet('node/add/post');
    $this->assertSession()->statusCodeEquals(403);
  }

}
