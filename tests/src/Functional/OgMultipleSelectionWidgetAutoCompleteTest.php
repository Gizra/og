<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Testing the audience field with multiple auto select widgets.
 *
 * @group og
 */
class OgMultipleSelectionWidgetAutoCompleteTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

  /**
   * A group node for user 1.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group;

  /**
   * Group owner.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * OG role manager service.
   *
   * @var \Drupal\og\OgRoleManagerInterface
   */
  protected $roleManager;

  /**
   * The role of the user.
   *
   * @var \Drupal\og\OgRoleInterface
   */
  protected $role;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create group node types.
    $this->createContentType(['type' => 'group_type']);
    Og::addGroup('node', 'group_type');

    NodeType::create(['type' => 'group_content'])->save();

    // Set the field widget as autocomplete..
    $settings = [
      'form_display' => [
        'type' => 'entity_reference_autocomplete',
      ],
    ];
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Add another field.
    $settings += [
      'field_name' => 'override_name',
      'field_config' => ['label' => 'Second group reference'],
    ];
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Create users.
    $this->user = $this->drupalCreateUser();

    // Create groups.
    $this->group = Node::create([
      'type' => 'group_type',
      'title' => 'group1',
      'uid' => $this->user->id(),
    ]);
    $this->group->save();

    // Adding to the member role the appropriate permission.
    $this->role = OgRole::create();
    $this->role
      ->setName('content_editor')
      ->setLabel('Content group editor')
      ->setGroupType('node')
      ->setGroupBundle('group_type')
      ->grantPermission('create group_content content')
      ->save();

    // Create a role.
    $role_storage = $this->container->get('entity.manager')->getStorage('user_role');
    $role_storage->create([
      'id' => 'dummy_role',
      'permissions' => [
        'create group_content content',
        'edit own group_content content',
        'edit any group_content content',
      ],
    ])->save();
  }

  /**
   * Test the auto complete widget for non group member.
   */
  public function testAutoCompleteForNonGroupMember() {
    $this->drupalLogin($this->user);

    // Submit a form without any group.
    $edit = [
      'title[0][value]' => 'First group name',
    ];

    $this->drupalGet('node/add/group_content');
    $this->submitForm($edit, 'Save');

    // Verify the error appeared.
    $this->assertSession()->pageTextContains('One of the fields Groups audience, Second group reference is required.');

    // Submit the form when the first field is populated.
    $edit = [
      'title[0][value]' => 'First group name',
      'og_audience[0][target_id]' => $this->group->label() . ' (' . $this->group->id() . ')',
    ];

    $this->drupalGet('node/add/group_content');
    $this->submitForm($edit, 'Save');

    // Make sure the node has created.
    $this->assertSession()->pageTextContains('First group name has been created.');

    $query = $this->container->get('entity_type.manager')->getStorage('node')->getQuery();
    $result = $query
      ->condition('type', 'group_content')
      ->range(0, 1)
      ->sort('nid', 'DESC')
      ->execute();
    $gcid = reset($result);

    // Edit the node and remove the reference.
    $edit = [
      'title[0][value]' => 'Second group name',
      'og_audience[0][target_id]' => '',
    ];

    $this->drupalGet('node/' . $gcid . '/edit');
    $this->submitForm($edit, 'Save');

    // Make sure the error appeared.
    $this->assertSession()->pageTextContains('One of the fields Groups audience, Second group reference is required.');

    // Grant to the user site wide permission.
    $this->user->addRole('dummy_role');
    $this->user->save();

    // Remove the reference.
    $edit = [
      'title[0][value]' => 'Second group name',
      'og_audience[0][target_id]' => '',
    ];

    $this->drupalGet('node/' . $gcid . '/edit');
    $this->submitForm($edit, 'Save');

    // Make sure the node was updated.
    $this->assertSession()->pageTextContains('Second group name has been updated.');

    // Create a new group content without any group.
    $edit = [
      'title[0][value]' => 'Third group name',
    ];

    $this->drupalGet('node/add/group_content');
    $this->submitForm($edit, 'Save');

    // Make sure the node was created.
    $this->assertSession()->pageTextContains('Third group name has been created.');
  }

}
