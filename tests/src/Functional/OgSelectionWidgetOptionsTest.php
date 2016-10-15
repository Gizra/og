<?php

namespace Drupal\Tests\og\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
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
    NodeType::create(['type' => 'group1'])->save();
    NodeType::create(['type' => 'group2'])->save();
    Og::addGroup('node', 'group1');
    Og::addGroup('node', 'group2');

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
      'type' => 'group1',
      'title' => $this->randomString(),
      'uid' => $this->groupOwnerUser->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'type' => 'group2',
      'title' => $this->randomString(),
      'uid' => $this->groupOwnerUser->id(),
    ]);
    $this->group2->save();

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
      ->grantPermission('create node group_content')
      ->save();

    $this->drupalGet('node/add/group_content');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->optionExists('Groups audience', '_none');
    $this->assertSession()->optionExists('Groups audience', $this->group1->label());
    $this->assertSession()->optionNotExists('Groups audience', $this->group2->label());

    // Verify the group owner.
    $this->drupalLogin($this->groupOwnerUser);
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->optionExists('Groups audience', $this->group1->label());
    $this->assertSession()->optionExists('Groups audience', $this->group2->label());

    // Verify the site-wide administrator.
    $this->drupalLogin($this->groupAdministratorUser);
    $this->drupalGet('node/add/group_content');
    $this->assertSession()->optionExists('Groups audience', $this->group1->label());
    $this->assertSession()->optionExists('Groups audience', $this->group2->label());
  }

}
