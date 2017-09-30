<?php

namespace Drupal\Tests\og\Functional;

use Behat\Mink\Exception\ResponseTextException;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\simpletest\ContentTypeCreationTrait;
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

    // Use a select list widget for the audience field, so it's easier to get
    // all the values.
    $settings = [
      'form_display' => [
        'type' => 'entity_reference_autocomplete',
      ],
    ];
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Create users.
    $this->user = $this->drupalCreateUser();

    // Create groups.
    $this->group = Node::create([
      'type' => 'group_type',
      'title' => 'group1',
      'uid' => $this->user1->id(),
    ]);
    $this->group->save();

    // Adding to the member role the appropriate permission.
    $this->role = OgRole::create();
    $this->role
      ->setName('content_editor')
      ->setLabel('Content group editor')
      ->setGroupType('node')
      ->setGroupBundle('group_type')
      ->grantPermission('create node group_content')
      ->save();
  }

  /**
   * Test the auto complete widget for non group member.
   */
  public function testAutoCompleteForNonGroupMember() {
    // Login as the user.

    // Submit a form without any group.

    // Verify the error appeared.

    // Submit the form when the first field is populated.

    // Make sure the node has created.

    // Edit the node and remove the reference.

    // Make sure the error appeared.

    // Assign the group content to the same group but in another field.

    // Make sure the node updated.

    // Grant to the user site wide permission.

    // Remove the reference and make sure the node was updated.

    // Create a new group content without any group and make sure the node was
    // created.

    // Remove the site wide permissions to the user.

    // Edit the node.

    // Make sure the error appeared.

    // Update the node with a group reference and make sure the node was
    // updated.
  }

}
