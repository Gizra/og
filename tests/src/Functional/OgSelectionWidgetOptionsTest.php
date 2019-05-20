<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the various OG handler options.
 *
 * @group og
 */
class OgSelectionWidgetOptionsTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

  /**
   * A group node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group1;

  /**
   * A group node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group2;


  /**
   * An unpublished group node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $unpublishedGroup;

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
   * A non-member user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $nonMemberUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create group node types.
    $this->createContentType(['type' => 'group_type1']);
    $this->createContentType(['type' => 'group_type2']);

    Og::addGroup('node', 'group_type1');
    Og::addGroup('node', 'group_type2');

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
    $this->groupMemberUser = $this->drupalCreateUser();
    $this->groupOwnerUser = $this->drupalCreateUser();
    $this->groupAdministratorUser = $this->drupalCreateUser(['administer group']);
    $this->nonMemberUser = $this->drupalCreateUser();

    // Create groups.
    $this->group1 = Node::create([
      'type' => 'group_type1',
      'title' => 'group1',
      'uid' => $this->groupOwnerUser->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'type' => 'group_type2',
      'title' => 'group2',
      'uid' => $this->groupOwnerUser->id(),
    ]);
    $this->group2->save();

    $this->unpublishedGroup = Node::create([
      'type' => 'group_type2',
      'title' => 'unpublished group',
      'uid' => $this->groupOwnerUser->id(),
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);
    $this->unpublishedGroup->save();

    // Add member to group.
    Og::createMembership($this->group1, $this->groupMemberUser)->save();
    Og::createMembership($this->group2, $this->groupMemberUser)->save();
  }

  /**
   * Tests the group audience widgets shows correct values.
   */
  public function testNonRequiredAudienceField() {
    // Non member user.
    $this->drupalLogin($this->nonMemberUser);
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->statusCodeEquals(403);

    // Group member without create permissions.
    $this->drupalLogin($this->groupMemberUser);
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->statusCodeEquals(403);

    // Grant create permission for the first group.
    $role = OgRole::getRole($this->group1->getEntityTypeId(), $this->group1->bundle(), OgRoleInterface::AUTHENTICATED);
    $role
      ->grantPermission('create group_content content')
      ->save();

    $this->drupalGet('node/add/group_content');
    // The user can't post group content site wide so the audience is required
    // field.
    // todo: is that correct?
    // $this->assertSession()->optionExists('Groups audience', '_none');.
    $this->assertSession()->optionExists('Groups audience', $this->group1->label());
    $this->assertSession()->optionNotExists('Groups audience', $this->group2->label());

    // Group owner.
    $this->drupalLogin($this->groupOwnerUser);
    $this->drupalGet('node/add/group_content');

    $this->assertSession()->optionExists('Groups audience', $this->group1->label());
    $this->assertSession()->optionExists('Groups audience', $this->group2->label());
    $this->assertSession()->optionNotExists('Groups audience', $this->unpublishedGroup->label());

    // Site-wide administrator.
    $this->drupalLogin($this->groupAdministratorUser);
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->optionExists('Groups audience', $this->group1->label());
    $this->assertSession()->optionExists('Groups audience', $this->group2->label());
    $this->assertSession()->optionNotExists('Groups audience', $this->unpublishedGroup->label());
  }

}
