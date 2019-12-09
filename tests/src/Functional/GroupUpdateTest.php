<?php

namespace Drupal\Tests\og\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgAccess;

/**
 * Tests the special permission 'update group'.
 *
 * @group og
 */
class GroupUpdateTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use OgMembershipCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'entity_test', 'og'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test content group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $contentGroup;

  /**
   * Test entity group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entityGroup;

  /**
   * Test group owner user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupOwner;

  /**
   * Test group editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupEditor;

  /**
   * Test normal user with no connection to the organic group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create dummy users.
    $this->groupOwner = $this->drupalCreateUser();
    $this->groupEditor = $this->drupalCreateUser();
    $this->normalUser = $this->drupalCreateUser();

    $this->setUpContentEntity();
    $this->setUpEntityTestEntity();
  }

  /**
   * Setup dummy content group entity and appropriate og permissions.
   */
  public function setUpContentEntity() {
    // Create a node bundle called 'content_group' and make it an Og group.
    $this->createContentType(['type' => 'content_group']);
    Og::groupTypeManager()->addGroup('node', 'content_group');

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
      'title' => $this->randomString(),
      'type' => 'content_group',
      'uid' => $this->groupOwner->id(),
      'status' => 1,
    ];
    $this->contentGroup = Node::create($values);
    $this->contentGroup->save();

    // Subscribe the editor user to the groups.
    $this->createOgMembership($this->contentGroup, $this->groupEditor, ['content_editor']);
  }

  /**
   * Setup dummy entity_test group entity and appropriate OG permissions.
   */
  public function setUpEntityTestEntity() {
    // Create an entity_test bundle called 'entity_group' and make it
    // an Og group.
    entity_test_create_bundle('entity_group');
    Og::groupTypeManager()->addGroup('entity_test', 'entity_group');

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
      'title' => $this->randomString(),
      'type' => 'entity_group',
      'uid' => $this->groupOwner->id(),
    ];
    $this->entityGroup = EntityTest::create($values);
    $this->entityGroup->save();

    $this->createOgMembership($this->entityGroup, $this->groupEditor, ['entity_editor']);
  }

  /**
   * Tests 'update group' special permission.
   *
   * @dataProvider ogUpdateAccessProvider
   */
  public function testUpdateAccess($entity) {
    // The editor should have permissions due to the 'update group' special
    // permission.
    $this->drupalLogin($this->groupEditor);
    $this->drupalGet($this->{$entity}->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // A normal user should not be able to edit the group.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($this->{$entity}->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Data provider for ::testUpdateAccess()
   *
   * @return array
   *   The names of the variables that will be tested.
   */
  public function ogUpdateAccessProvider() {
    return [
      // Mapping of operation 'update'.
      ['contentGroup'],
      // Mapping of operation 'edit'.
      ['entityGroup'],
    ];
  }

}
