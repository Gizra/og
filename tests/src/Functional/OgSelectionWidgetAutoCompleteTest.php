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
  protected $user1Group;

  /**
   * A group node for user 2.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $user2Group;

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
    $this->createContentType(['type' => 'group_type2']);
    Og::addGroup('node', 'group_type');

    NodeType::create(['type' => 'group_content'])->save();

    // Use a select list widget for the audience field, so it's easier to get
    // all the values.
    $settings = [
      'form_display' => [
        'type' => 'options_select',
      ],
    ];
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Create users.
    $this->user1Group = $this->drupalCreateUser();
    $this->user2Group = $this->drupalCreateUser();

    // Create groups.
    $this->user1Group = Node::create([
      'type' => 'group_type',
      'title' => 'group1',
      'uid' => $this->$this->user1Group->id(),
    ]);
    $this->user1Group->save();

    $this->user2Group = Node::create([
      'type' => 'group_type2',
      'title' => 'group2',
      'uid' => $this->user2Group->id(),
    ]);
    $this->user2Group->save();

  }

  /**
   * Test the auto complete widget for non group member.
   */
  public function testAutoCompleteForNonGroupMember() {
    $this->drupalLogin($this->user1);

    // Verify the user can reference group content to a groups which he owns.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'og_audience[0][target_id]' => $this->user1Group->label() . ' (' . $this->user1Group->id() . ')',
    ];

    $this->drupalGet('node/add/group_content');
    $this->submitForm($edit, 'Save');
  }

}
