<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgRoleInterface;
use Drupal\og\OgRoleManager;
use Drupal\og\OgRoleManagerInterface;
use Drupal\simpletest\ContentTypeCreationTrait;
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
      ->setId('authenticated')
      ->setName(OgRole::AUTHENTICATED)
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->grantPermission('create group_content content')
      ->save();
  }

  /**
   * Test the auto complete widget for non group member.
   */
  public function testAutoCompleteForNonGroupMember() {
    $this->drupalLogin($this->user1);

    // Verify the user can reference group content to a groups which he owns.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'og_audience[0][target_id]' => $this->group1->label() . ' (' . $this->group2->id() . ')',
    ];

    $this->drupalGet('node/add/group_content');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('You are not allowed to post content in the group ' . $this->group2->label());

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
    print_r($this->getSession()->getPage()->getContent());

  }

}
