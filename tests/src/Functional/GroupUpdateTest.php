<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Functional\GroupUpdateTest.
 */

namespace Drupal\Tests\og\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;
use Drupal\og\Plugin\Field\FieldType\OgMembershipReferenceItemList;
use Drupal\simpletest\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Tests the special permission 'update group'.
 *
 * @group og
 */
class GroupUpdateTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og'];

  /**
   * Test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

  /**
   * Test group owner user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $group_owner;

  /**
   * Test group editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $group_editor;

  /**
   * Test normal user with no connection to the organic group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normal_user;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Create a content type of bundle 'group' and make it an Og group.
    $this->createContentType(['type' => 'group']);
    Og::groupManager()->addGroup('node', 'group');

    // Create a role with only the 'update group' permission.
    $og_role = OgRole::create();
    $og_role
      ->setName('group_editor')
      ->setLabel('Group editor')
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->grantPermission('update group')
      ->save();

    // Create dummy users.
    $this->group_owner = $this->drupalCreateUser(['access content']);
    $this->group_editor = $this->drupalCreateUser(['access content']);
    $this->normal_user = $this->drupalCreateUser(['access content']);

    // Create a group content owned by the group owner.
    $values = [
      'title' => 'My awesome group',
      'type' => 'group',
      'uid' => $this->group_owner->id(),
    ];
    $this->group = Node::create($values);
    $this->group->save();

    // Subscribe the editor user to the group.
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->group_editor->id())
      ->setEntityId($this->group->id())
      ->setGroupEntityType($this->group->getEntityTypeId())
      ->setRoles([$og_role->id()])
      ->save();
  }

  /**
   * Tests 'update group' special permission.
   */
  function testUpdateAccess() {
    // The owner should have permissions due to the 'administer group' special
    // permission.
    $this->drupalLogin($this->group_owner);
    $this->drupalGet($this->group->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // The editor should have permissions due to the 'update group' special
    // permission.
    $this->drupalLogin($this->group_editor);
    $this->drupalGet($this->group->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // A normal user should not be able to edit the group.
    $this->drupalLogin($this->normal_user);
    $this->drupalGet($this->group->toUrl('edit-form'));
    $this->assertSession()->statusCodeNotEquals(200);
  }

}
