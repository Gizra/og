<?php

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access through OG's implementation of hook_entity_access().
 *
 * @group og
 */
class OgAccessHookTest extends KernelTestBase {

  use OgMembershipCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block_content',
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

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
   * Test non group content entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $nonGroupContent;

  /**
   * Test users.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $users;

  /**
   * Test roles.
   *
   * @var \Drupal\user\Entity\Role[]
   */
  protected $roles;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    // Create two roles: one for normal users, and one for administrators.
    foreach (['authenticated', 'administrator'] as $role_id) {
      $role = Role::create([
        'id' => $role_id,
        'label' => $role_id,
      ]);
      $role->grantPermission('access content');

      // Grant the 'administer group' permission to the administrator role.
      if ($role_id === 'administrator') {
        $role->grantPermission('administer group');
      }
      $role->save();
      $this->roles[$role_id] = $role;
    }

    // Create a test user for each membership type.
    $membership_types = [
      // The group owner.
      'owner',
      // A site administrator with the right to administer all groups.
      'group-admin',
      // A regular member of the group.
      'member',
      // A user that is not a member of the group.
      'non-member',
      // A blocked user.
      'blocked',
    ];
    foreach ($membership_types as $membership_type) {
      $user = User::create([
        'name' => $membership_type,
      ]);
      // Grant the 'administrator' role to the group administrator.
      if ($membership_type === 'group-admin') {
        $user->addRole('administrator');
      }
      $user->save();
      $this->users[$membership_type] = $user;
    }

    // Create a "group" bundle on the Custom Block entity type and turn it into
    // a group. Note we're not using the Entity Test entity for this since it
    // does not have real support for multiple bundles.
    BlockContentType::create(['id' => 'group'])->save();
    Og::groupTypeManager()->addGroup('block_content', 'group');

    // Create a group.
    $this->group = BlockContent::create([
      'title' => $this->randomString(),
      'type' => 'group',
      'uid' => $this->users['owner']->id(),
    ]);
    $this->group->save();

    // Create a group content type.
    $type = NodeType::create([
      'type' => 'group_content',
      'name' => $this->randomString(),
    ]);
    $type->save();
    $settings = [
      'field_storage_config' => [
        'settings' => [
          'target_type' => 'block_content',
        ],
      ],
    ];
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', 'group_content', $settings);

    // Grant members permission to edit their own content.
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::getRole('block_content', 'group', OgRoleInterface::AUTHENTICATED);
    $role->grantPermission('edit own group_content content');
    $role->save();

    // Subscribe the normal member and the blocked member to the group.
    foreach (['member', 'blocked'] as $membership_type) {
      $state = $membership_type === 'member' ? OgMembershipInterface::STATE_ACTIVE : OgMembershipInterface::STATE_BLOCKED;
      $this->createOgMembership($this->group, $this->users[$membership_type], NULL, $state);
    }

    // Create three group content items, one owned by the group owner, one by
    // the member, and one by the blocked user.
    foreach (['owner', 'member', 'blocked'] as $membership_type) {
      $this->groupContent[$membership_type] = Node::create([
        'title' => $this->randomString(),
        'type' => 'group_content',
        'uid' => $this->users[$membership_type]->id(),
        OgGroupAudienceHelperInterface::DEFAULT_FIELD => [['target_id' => $this->group->id()]],
      ]);
      $this->groupContent[$membership_type]->save();
    }

    $this->nonGroupContent = Node::create([
      'title' => $this->randomString(),
      'type' => 'group_content',
      'uid' => $this->users['member']->id(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => [],
    ]);
    $this->nonGroupContent->save();
  }

  /**
   * Tests access to entity operations through the access hook.
   *
   * @param string $user
   *   The name of the user to test.
   * @param array $expected_results
   *   An associative array indicating whether the user should have the right to
   *   edit content owned by the user represented by the array key.
   *
   * @dataProvider entityOperationAccessProvider
   */
  public function testEntityOperationAccess($user, array $expected_results) {
    foreach ($expected_results as $group_content => $expected_result) {
      /** @var \Drupal\Core\Access\AccessResult $result */
      $result = og_entity_access($this->groupContent[$group_content], 'update', $this->users[$user]);
      $this->assertEquals($expected_result, $result->isAllowed());
    }

    $result = og_entity_access($this->nonGroupContent, 'update', $this->users['member']);
    $this->assertEquals(TRUE, $result->isNeutral());
  }

  /**
   * Data provider for ::testEntityOperationAccess().
   *
   * @return array
   *   And array of test data sets. Each set consisting of:
   *   - The name of the user to test.
   *   - An associative array indicating whether the user should have the right
   *     to edit content owned by the user represented by the array key.
   */
  public function entityOperationAccessProvider() {
    return [
      [
        // The administrator should have the right to edit group content items
        // owned by any user.
        'group-admin',
        [
          'owner' => TRUE,
          'member' => TRUE,
          'blocked' => TRUE,
        ],
      ],
      [
        // Members should only have the right to edit their own group content.
        'member',
        [
          'owner' => FALSE,
          'member' => TRUE,
          'blocked' => FALSE,
        ],
      ],
      [
        // The non-member cannot edit any group content.
        'non-member',
        [
          'owner' => FALSE,
          'member' => FALSE,
          'blocked' => FALSE,
        ],
      ],
      [
        // The blocked member cannot edit any group content, not even their own.
        'blocked',
        [
          'owner' => FALSE,
          'member' => FALSE,
          'blocked' => FALSE,
        ],
      ],
    ];
  }

}
