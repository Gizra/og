<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Functional\GroupUpdateTest.
 */

namespace Drupal\Tests\og\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgMembershipInterface;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\Tests\BrowserTestBase;

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
  public static $modules = ['node', 'entity_test', 'og'];

  /**
   * Test content group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $content_group;

  /**
   * Test entity group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity_group;

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
  protected function setUp() {
    parent::setUp();

    // Create dummy users.
    $this->group_owner = $this->drupalCreateUser();
    $this->group_editor = $this->drupalCreateUser();
    $this->normal_user = $this->drupalCreateUser();

    $this->setUpContentEntity();
    $this->setUpEntityTestEntity();
  }

  /**
   * Setup dummy content group entity and appropriate og permissions.
   */
  public function setUpContentEntity() {
    // Create a node bundle called 'content_group' and make it an Og group.
    $this->createContentType(['type' => 'content_group']);
    Og::groupManager()->addGroup('node', 'content_group');

    // Create a role with only the 'update group' permission.
    $content_editor_role = OgRole::create();
    $content_editor_role
      ->setName('content_editor')
      ->setLabel('Content group editor')
      ->setGroupType('node')
      ->setGroupBundle('content_group')
      ->grantPermission(OgAccess::UPDATE_GROUP_PERMISSION)
      ->save();

    // Create a group content owned by the group owner.
    $values = [
      'title' => 'My awesome content group',
      'type' => 'content_group',
      'uid' => $this->group_owner->id(),
      'status' => 1,
    ];
    $this->content_group = Node::create($values);
    $this->content_group->save();

    // Subscribe the editor user to the groups.
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->group_editor->id())
      ->setEntityId($this->content_group->id())
      ->setGroupEntityType($this->content_group->getEntityTypeId())
      ->setRoles([$content_editor_role->id()])
      ->save();
  }

  /**
   * Setup dummy entity_test group entity and appropriate og permissions.
   */
  public function setUpEntityTestEntity() {
    // Create an entity_test bundle called 'entity_group' and make it
    // an Og group.
    entity_test_create_bundle('entity_group');
    Og::groupManager()->addGroup('entity_test', 'entity_group');

    // Create a role with only the 'update group' permission.
    $entity_editor_role = OgRole::create();
    $entity_editor_role
      ->setName('entity_editor')
      ->setLabel('Entity group editor')
      ->setGroupType('entity_test')
      ->setGroupBundle('entity_group')
      ->grantPermission(OgAccess::UPDATE_GROUP_PERMISSION)
      ->save();

    // Create an entity_test entity owned by the group owner.
    $values = [
      'title' => 'My awesome group',
      'type' => 'entity_group',
      'uid' => $this->group_owner->id(),
    ];
    $this->entity_group = EntityTest::create($values);
    $this->entity_group->save();

    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->group_editor->id())
      ->setEntityId($this->entity_group->id())
      ->setGroupEntityType($this->entity_group->getEntityTypeId())
      ->setRoles([$entity_editor_role->id()])
      ->save();
  }

  /**
   * Tests 'update group' special permission.
   *
   * @dataProvider ogUpdateAccessProvider
   */
  public function testUpdateAccess($entity) {
    // The editor should have permissions due to the 'update group' special
    // permission.
    $this->drupalLogin($this->group_editor);
    $this->drupalGet($this->{$entity}->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // A normal user should not be able to edit the group.
    $this->drupalLogin($this->normal_user);
    $this->drupalGet($this->{$entity}->toUrl('edit-form'));
    $this->assertSession()->statusCodeNotEquals(200);
  }

  /**
   * Data provider for ::testUpdateAccess()
   *
   * @return array
   */
  public function ogUpdateAccessProvider() {
    return [
      ['content_group'], // Mapping of operation 'update'.
      ['entity_group'], // Mapping of operation 'edit'.
    ];
  }
}
