<?php

namespace Drupal\Tests\og\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;
use Drupal\simpletest\ContentTypeCreationTrait;

/**
 * Tests if the user has access to groups and group content.
 *
 * @group og
 */
class AccessTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content', 'node', 'og'];

  /**
   * A test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

  /**
   * Test group content entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $groupContent;

  /**
   * Test users.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $users;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    // Create a number of test users.
    $users = [
      // The group owner.
      'owner' => ['access content'],
      // A site administrator with the right to administer all groups.
      'group-admin' => ['administer group', 'access content'],
      // A regular member of the group.
      'member' => ['access content'],
      // A user that is not a member of the group.
      'non-member' => ['access content'],
    ];
    foreach ($users as $user => $permissions) {
      $this->users[$user] = $this->drupalCreateUser($permissions, $user);
    }

    // Create a "group" bundle on the Custom Block entity type and turn it into
    // a group. Note we're not using the Entity Test entity for this since it
    // does not have real support for multiple bundles.
    BlockContentType::create(['type' => 'group']);
    Og::groupManager()->addGroup('block_content', 'group');

    // Create a group.
    $this->group = BlockContent::create([
      'title' => $this->randomString(),
      'type' => 'group',
      'uid' => $this->users['owner']->id(),
    ]);
    $this->group->save();

    // Create a group content type.
    $this->createContentType(['type' => 'group_content']);
    $settings = [
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'block_content',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Grant members permission to edit their own content.
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = $this->container->get('entity_type.manager')
      ->getStorage('og_role')
      ->load('block_content-group-member');
    $role->grantPermission('edit own group_content content');
    $role->save();

    // Subscribe the member to the group.
    /** @var \Drupal\og\Entity\OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->users['member']->id())
      ->setEntityId($this->group->id())
      ->setGroupEntityType($this->group->getEntityTypeId())
      ->addRole($role->id())
      ->save();

    // Create two group content items, one owned by the group owner, and one by
    // the member.
    foreach (['owner', 'member'] as $user) {
      $this->groupContent[$user] = Node::create([
        'title' => $this->randomString(),
        'type' => 'group_content',
        'uid' => $this->users[$user]->id(),
        OgGroupAudienceHelper::DEFAULT_FIELD => [['target_id' => $this->group->id()]],
      ]);
      $this->groupContent[$user]->save();
    }
  }

  /**
   * Tests access to entity operations through the user interface.
   *
   * Note that this does not use a dataProvider because of the slow performance
   * of setting up functional tests.
   */
  public function testEntityOperationAccess() {
    $test_matrix = [
      // The administrator should have the right to edit both group content
      // items.
      'group-admin' => ['owner' => TRUE, 'member' => TRUE],
      // The member should only have the right to edit their own group content.
      'member' => ['owner' => FALSE, 'member' => TRUE],
      // The non-member cannot edit any group content.
      'non-member' => ['owner' => FALSE, 'member' => FALSE],
    ];
    foreach ($test_matrix as $user => $expected_results) {
      $this->drupalLogin($this->users[$user]);
      foreach ($expected_results as $group_content => $expected_result) {
        $this->drupalGet($this->groupContent[$group_content]->toUrl('edit-form'));
        $expected_response = $expected_result ? 200 : 403;
        $this->assertSession()->statusCodeEquals($expected_response);
      }
    }
  }

}
