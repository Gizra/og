<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Tests the various OG handler options.
 *
 * @group og
 */
class OgSelectionWidgetOptionsTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

  /**
   * A group object.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group1;

  /**
   * A group object.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group2;

  /**
   * Demo user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupMemberUser;

  /**
   * Group owner.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupOwnerUser;

  /**
   * Administrator groups user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupAdministratorUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Setting content types.
    NodeType::create(['type' => 'group'])->save();
    NodeType::create(['type' => 'group_content'])->save();

    // Setting up groups and group content relations.
    $settings = [
      'form_display' => [
        'type' => 'options_buttons',
      ],
    ];
    Og::addGroup('node', 'group');
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Creating users.
    $this->groupMemberUser = $this->drupalCreateUser([
      'access content',
      'create group_content content',
    ]);

    $this->groupOwnerUser = $this->drupalCreateUser([
      'create group_content content',
    ]);

    $this->groupAdministratorUser = $this->drupalCreateUser([
      'administer group',
      'create group_content content',
    ]);

    // Create groups.
    $this->group1 = Node::create([
      'type' => 'group',
      'title' => $this->randomString(),
      'uid' => $this->groupOwnerUser->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'type' => 'group',
      'title' => $this->randomString(),
      'uid' => $this->groupOwnerUser->id(),
    ]);
    $this->group2->save();
  }

  /**
   * Tests adding groups, and node access.
   */
  public function testFields() {
    $this->drupalLogin($this->groupMemberUser);
    $this->drupalGet('node/add/group_content');

    // Verify the user can't see the groups in the selection handler.
    $this->assertSession()->pageTextNotContains($this->group1->label());
    $this->assertSession()->pageTextNotContains($this->group2->label());

    // Assign the permission.
    $role = OgRole::getRole($this->group1->getEntityTypeId(), $this->group1->bundle(), OgRoleInterface::AUTHENTICATED);
    $role
      ->grantPermission('create node group_content')
      ->save();

    Og::createMembership($this->group1, $this->groupMemberUser)
      ->addRole($role)
      ->save();

    // Verify the user can reference to the group.
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->pageTextContains($this->group1->label());
    $this->assertSession()->pageTextNotContains($this->group2->label());

    // Verify the group can reference to all the groups.
    $this->drupalLogin($this->groupOwnerUser);
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->pageTextContains($this->group1->label());
    $this->assertSession()->pageTextContains($this->group2->label());

    // Verify the groups administrator can reference to all the groups.
    $this->drupalLogin($this->groupAdministratorUser);
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->pageTextContains($this->group1->label());
    $this->assertSession()->pageTextContains($this->group2->label());
  }

}
