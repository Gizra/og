<?php

namespace Drupal\Tests\og\Functional;

use Behat\Mink\Exception\ResponseTextException;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Og auto complete widget.
 *
 * @group og
 */
class OgSelectionWidgetAutoCompleteTest extends BrowserTestBase {

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
  protected $group1;

  /**
   * A group node for user 2.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group2;

  /**
   * Group owner.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user1;

  /**
   * Group owner.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

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
    $this->user1 = $this->drupalCreateUser();
    $this->user2 = $this->drupalCreateUser();

    // Create groups.
    $this->group1 = Node::create([
      'type' => 'group_type',
      'title' => 'group1',
      'uid' => $this->user1->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'type' => 'group_type',
      'title' => 'group2',
      'uid' => $this->user2->id(),
    ]);
    $this->group2->save();

    // Adding to the member role the appropriate permission.
    $this->role = OgRole::create();
    $this->role
      ->setName('content_editor')
      ->setLabel('Content group editor')
      ->setGroupType('node')
      ->setGroupBundle('group_type')
      ->grantPermission('create group_content content')
      ->save();
  }

  /**
   * Test the auto complete widget for non group member.
   */
  public function testAutoCompleteForNonGroupMember() {
    $this->drupalLogin($this->user1);

    // Verify that users can reference group content to groups they own.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'og_audience[0][target_id]' => $this->group1->label() . ' (' . $this->group2->id() . ')',
    ];

    $this->drupalGet('node/add/group_content');
    $this->submitForm($edit, 'Save');

    // When using 8.4, ValidReferenceConstraintValidator is prevent from
    // ValidOgMembershipReferenceConstraintValidator message to appear. We need
    // to see how we can override that so the user would have a better
    // understanding why the reference is invalid.
    try {
      $this->assertSession()->pageTextContains('You are not allowed to post content in the group ' . $this->group2->label());
    }
    catch (ResponseTextException $e) {
      $this->assertSession()->pageTextContains('This entity (node: ' . $this->group2->id() . ') cannot be referenced.');
    }
    catch (\Exception $e) {
      throw new \Exception('Both of the errors for the invalid group reference did not appear on the screen.');
    }

    // Add the member to the group.
    Og::createMembership($this->group2, $this->user1)->addRole($this->role)->save();

    $this->drupalLogin($this->user1);
    // Testing the user can add group content after being a member of the group.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'og_audience[0][target_id]' => $this->group1->label() . ' (' . $this->group2->id() . ')',
    ];

    $this->drupalGet('node/add/group_content');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($edit['title[0][value]'] . ' has been created.');
  }

}
