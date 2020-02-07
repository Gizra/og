<?php

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access to content by OgMembership.
 *
 * @group og
 */
class AccessByOgMembershipTest extends KernelTestBase {

  use ContentTypeCreationTrait;
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
   * Test users.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $users;

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

    // Create a user role for a standard authenticated user.
    $role = Role::create([
      'id' => 'authenticated',
      'label' => 'authenticated',
    ]);
    $role->grantPermission('access content');
    $role->save();

    // Create a test user for each membership type.
    $membership_types = [
      // The group owner.
      'owner',
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

    // Grant both members and non-members permission to edit any group content.
    foreach ([OgRoleInterface::AUTHENTICATED, OgRoleInterface::ANONYMOUS] as $role_name) {
      /** @var \Drupal\og\Entity\OgRole $role */
      $role = OgRole::getRole('block_content', 'group', $role_name);

      $role
        ->grantPermission('edit any group_content content')
        ->save();
    }

    $role = OgRole::getRole('block_content', 'group', OgRoleInterface::AUTHENTICATED);

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
  }

  /**
   * Tests exception is thrown when trying to save non-member role.
   */
  public function testNonMemberRoleMembershipSave() {
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::getRole('block_content', 'group', OgRoleInterface::ANONYMOUS);

    $role
      ->grantPermission('edit any group_content content')
      ->save();

    $membership = OgMembership::create();
    $this->expectException(EntityStorageException::class);
    $membership
      ->setOwner($this->users['non-member'])
      ->setGroup($this->group)
      ->addRole($role)
      ->setState(OgMembershipInterface::STATE_ACTIVE)
      ->save();
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
        // Members should have the right to edit any group content.
        'member',
        [
          'owner' => TRUE,
          'member' => TRUE,
          'blocked' => TRUE,
        ],
      ],
      [
        // Non-members should have the right to edit any group content.
        'non-member',
        [
          'owner' => TRUE,
          'member' => TRUE,
          'blocked' => TRUE,
        ],
      ],
      [
        // Blocked members cannot edit any group content, not even their own.
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
