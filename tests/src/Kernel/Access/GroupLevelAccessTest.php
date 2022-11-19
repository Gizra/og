<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests user access to group level entity operations and permissions.
 *
 * @coversDefaultClass \Drupal\og\OgAccess
 * @group og
 */
class GroupLevelAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'og',
    'entity_test',
    'options',
  ];

  /**
   * The OgAccess service, this is the system under test.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $nonMemberUser;

  /**
   * The group owner.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $ownerUser;

  /**
   * An admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A second administrator which has an alternative administration role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $alternativeAdminUser;

  /**
   * A group entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group;

  /**
   * The machine name of the group's bundle.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installSchema('system', 'sequences');

    $this->ogAccess = $this->container->get('og.access');

    // Declare the test entity as being a group.
    $this->groupBundle = mb_strtolower($this->randomMachineName());
    Og::groupTypeManager()->addGroup('entity_test', $this->groupBundle);

    // Create users, and make sure user ID 1 isn't used.
    User::create(['name' => $this->randomString()])->save();

    // Create a user that represents the group owner.
    $this->ownerUser = User::create(['name' => $this->randomString()]);
    $this->ownerUser->save();

    // Create a group and associate with the group owner.
    $this->group = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->ownerUser->id(),
    ]);
    $this->group->save();

    // Create a non-member.
    $this->nonMemberUser = User::create(['name' => $this->randomString()]);
    $this->nonMemberUser->save();

    // Create an administrator user using the role that is created automatically
    // when the group is created.
    // @see \Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultRoles()
    $admin_role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ADMINISTRATOR);
    $this->adminUser = $this->createUserWithOgRole($admin_role);

    // Create another administrator role and assign it to a second test user.
    // This is a supported use case: it is possible to have multiple
    // administration roles.
    /** @var \Drupal\og\OgRoleInterface $alternative_admin_role */
    $alternative_admin_role = $this->createOgRole([], TRUE);
    $this->alternativeAdminUser = $this->createUserWithOgRole($alternative_admin_role);
  }

  /**
   * Test access to an arbitrary permission.
   *
   * @covers ::userAccess
   */
  public function testUserAccessArbitraryPermissions() {
    [$roles, $users] = $this->setupUserAccessArbitraryPermissions();

    // Check the user that has an arbitrary permission in both groups. It should
    // have permission to the permission in group 1.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $users['has_permission_in_both_groups'])->isAllowed());
    // This user should not have access to 'some_perm_2' as that was only
    // assigned to group 2.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm_2', $users['has_permission_in_both_groups'])->isNeutral());
    // Check the permission of group 1 again.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $users['has_permission_in_both_groups'])->isAllowed());

    // A member user without the correct role.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $users['has_no_permission'])->isNeutral());

    // A non-member user.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isNeutral());

    // Grant the arbitrary permission to non-members and check that our
    // non-member now has the permission.
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('some_perm')
      ->save();
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isAllowed());

    // Revoke the arbitrary permission again for non-members and check that our
    // poor non-member loses the permission.
    $role
      ->revokePermission('some_perm')
      ->save();
    $this->assertFalse($this->ogAccess->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isAllowed());

    // Make the non-member a member with the role. They should regain the
    // permission.
    $membership = Og::createMembership($this->group, $this->nonMemberUser);
    $membership
      ->addRole($roles['arbitrary_permission'])
      ->save();
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isAllowed());

    // Group admin user should have access regardless.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertTrue($this->ogAccess->userAccess($this->group, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // Also group admins that have a custom admin role should have access.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $this->alternativeAdminUser)->isAllowed());
    $this->assertTrue($this->ogAccess->userAccess($this->group, $this->randomMachineName(), $this->alternativeAdminUser)->isAllowed());

    // The admin user should no longer have access if the role is demoted from
    // being an admin role.
    $admin_role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ADMINISTRATOR);
    $admin_role->setIsAdmin(FALSE)->save();
    $this->assertFalse($this->ogAccess->userAccess($this->group, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertFalse($this->ogAccess->userAccess($this->group, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // The group owner should have access using the default configuration.
    $this->assertTrue($this->ogAccess->userAccess($this->group, 'some_perm', $this->ownerUser)->isAllowed());

    // Change the configuration to no longer grant full access to the group
    // owner. This should revoke access.
    $this->config('og.settings')->set('group_manager_full_access', FALSE)->save();
    $this->assertFalse($this->ogAccess->userAccess($this->group, 'some_perm', $this->ownerUser)->isAllowed());
  }

  /**
   * Sets up a matrix of users and roles with arbitrary permissions.
   *
   * @return array[]
   *   A tuple containing the created test roles and users.
   */
  protected function setupUserAccessArbitraryPermissions() {
    $roles = [];
    $users = [];

    // Create another group to test per group/per account permission caching.
    // This is a group of the same entity type and bundle.
    $alternate_group = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->ownerUser->id(),
    ]);
    $alternate_group->save();

    // Create a role with an arbitrary permission to test with.
    $roles['arbitrary_permission'] = $this->createOgRole(['some_perm']);

    // Create a role with an arbitrary permission which will only be granted to
    // a member of the second group.
    $alternate_role = OgRole::create();
    $alternate_role
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($alternate_group->getEntityTypeId())
      ->setGroupBundle($alternate_group->bundle())
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm_2')
      ->save();
    $roles['alternate'] = $alternate_role;

    // Create a user which is a member of both test groups and has an arbitrary
    // permission in both. This allows us to test that permissions do not leak
    // between different groups.
    $user = $this->createUserWithOgRole($roles['arbitrary_permission']);

    $membership = Og::createMembership($alternate_group, $user);
    $membership
      ->addRole($alternate_role)
      ->save();

    $users['has_permission_in_both_groups'] = $user;

    // Create a user which is a member and has a role without any permissions.
    $role_without_permissions = $this->createOgRole();
    $user = $this->createUserWithOgRole($role_without_permissions);
    $users['has_no_permission'] = $user;

    return [$roles, $users];
  }

  /**
   * Test access to the entity operation permissions on groups.
   *
   * This tests that the entity operations "update" and "delete" are controlled
   * by the respective group level permissions.
   *
   * @covers ::userAccessEntityOperation
   * @dataProvider groupEntityOperationPermissionsTestProvider
   */
  public function testGroupEntityOperationPermissions(string $user, array $access_matrix): void {
    $users = $this->setupGroupEntityOperationPermissions();
    $user = $users[$user];

    foreach ($access_matrix as $operation => $expected_access) {
      // Check that the correct access is returned.
      $result = $this->ogAccess->userAccessEntityOperation($operation, $this->group, $user);
      $this->assertEquals($expected_access, $result->isAllowed());

      // Also check that the access result is correctly communicated to
      // hook_entity_access().
      $arguments = [$this->group, $operation, $user];
      $hook_result = \Drupal::moduleHandler()->invokeAll('entity_access', $arguments);

      // The hook returns an array of access results, add them all up.
      if (empty($hook_result)) {
        $result = AccessResult::neutral();
      }
      else {
        /** @var \Drupal\Core\Access\AccessResultInterface $result */
        $result = array_shift($hook_result);
        foreach ($hook_result as $other) {
          $result = $result->orIf($other);
        }
      }

      $this->assertEquals($expected_access, $result->isAllowed());
    }
  }

  /**
   * Returns test users with permissions to perform group entity operations.
   *
   * @return \Drupal\user\UserInterface[]
   *   The test users.
   */
  protected function setupGroupEntityOperationPermissions(): array {
    // Return the users from the generic test setup.
    $users = [
      'owner' => $this->ownerUser,
      'non-member' => $this->nonMemberUser,
      'admin' => $this->adminUser,
      'alternative-admin' => $this->alternativeAdminUser,
    ];

    // A group member with the group level permission 'update group' which maps
    // to the 'update' entity operation.
    $role_with_update_permission = $this->createOgRole([OgAccess::UPDATE_GROUP_PERMISSION]);
    $user = $this->createUserWithOgRole($role_with_update_permission);
    $users['update'] = $user;

    // A group member with the group level permission 'delete group' which maps
    // to the 'delete' entity operation.
    $role_with_delete_permission = $this->createOgRole([OgAccess::DELETE_GROUP_PERMISSION]);
    $user = $this->createUserWithOgRole($role_with_delete_permission);
    $users['delete'] = $user;

    return $users;
  }

  /**
   * Provides test data to check access to group level entity permissions.
   *
   * @see ::testDefaultGroupPermissions()
   */
  public function groupEntityOperationPermissionsTestProvider(): array {
    return [
      [
        // The user performing the entity operations.
        'owner',
        // Whether or not the user should have access to the group entity
        // operation.
        ['update' => TRUE, 'delete' => TRUE],
      ],
      [
        'non-member',
        ['update' => FALSE, 'delete' => FALSE],
      ],
      [
        'delete',
        ['update' => FALSE, 'delete' => TRUE],
      ],
      [
        'update',
        ['update' => TRUE, 'delete' => FALSE],
      ],
      [
        'admin',
        ['update' => TRUE, 'delete' => TRUE],
      ],
      [
        'alternative-admin',
        ['update' => TRUE, 'delete' => TRUE],
      ],
    ];
  }

  /**
   * Creates an OG role with the given permissions and admin flag.
   *
   * @param string[] $permissions
   *   The permissions to set on the role.
   * @param bool $is_admin
   *   Whether or not this is an admin role.
   *
   * @return \Drupal\og\OgRoleInterface
   *   The newly created role.
   */
  protected function createOgRole(array $permissions = [], bool $is_admin = FALSE): OgRoleInterface {
    /** @var \Drupal\og\OgRoleInterface $role */
    $role = OgRole::create();
    $role
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->setIsAdmin($is_admin);

    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }

    $role->save();

    return $role;
  }

  /**
   * Creates a test user and assigns it a membership with the given role.
   *
   * @param \Drupal\og\OgRoleInterface $role
   *   The OG role to assign to the newly created user.
   *
   * @return \Drupal\user\UserInterface
   *   The newly created user.
   */
  protected function createUserWithOgRole(OgRoleInterface $role): UserInterface {
    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    $membership = Og::createMembership($this->group, $user);
    $membership
      ->addRole($role)
      ->save();

    return $user;
  }

}
