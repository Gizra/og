<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;

/**
 * Tests user access by group level permissions.
 *
 * @coversDefaultClass \Drupal\og\OgAccess
 * @group og
 */
class OgUserAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'og',
    'entity_test',
  ];

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $nonMemberUser;

  /**
   * An user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $updateUser;

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
   * The OG role that has the special permission 'update group'.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $ogRoleWithUpdatePermission;

  /**
   * The OG role that doesn't have the permission we check for.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $ogRoleWithoutPermission;

  /**
   * The OG role that doesn't have the permission we check for.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $ogAdminRole;

  /**
   * A custom OG admin role.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $ogAlternativeAdminRole;

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

    $this->groupBundle = mb_strtolower($this->randomMachineName());

    // Create users, and make sure user ID 1 isn't used.
    User::create(['name' => $this->randomString()])->save();

    $this->ownerUser = User::create(['name' => $this->randomString()]);
    $this->ownerUser->save();

    // A non-member.
    $this->nonMemberUser = User::create(['name' => $this->randomString()]);
    $this->nonMemberUser->save();

    // A group member the special permission 'update group'.
    $this->updateUser = User::create(['name' => $this->randomString()]);
    $this->updateUser->save();

    // Admin user.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();

    // Second admin user which uses an alternative administration role.
    $this->alternativeAdminUser = User::create(['name' => $this->randomString()]);
    $this->alternativeAdminUser->save();

    // Declare the test entity as being a group.
    Og::groupTypeManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with the group owner.
    $this->group = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->ownerUser->id(),
    ]);
    $this->group->save();

    $this->ogRoleWithUpdatePermission = OgRole::create();
    $this->ogRoleWithUpdatePermission
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->grantPermission(OgAccess::UPDATE_GROUP_PERMISSION)
      ->save();

    $this->ogRoleWithoutPermission = OgRole::create();
    $this->ogRoleWithoutPermission
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->save();

    // The administrator role is added automatically when the group is created.
    // @see \Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultRoles()
    $this->ogAdminRole = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ADMINISTRATOR);

    // Create a second administration role, since this is a supported use case.
    // It is possible to have multiple administration roles.
    $this->ogAlternativeAdminRole = OgRole::create();
    $this->ogAlternativeAdminRole
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->setIsAdmin(TRUE)
      ->save();

    // Check the special permission 'update group'.
    $membership = Og::createMembership($this->group, $this->updateUser);
    $membership
      ->addRole($this->ogRoleWithUpdatePermission)
      ->save();

    $membership = Og::createMembership($this->group, $this->adminUser);
    $membership
      ->addRole($this->ogAdminRole)
      ->save();

    $membership = Og::createMembership($this->group, $this->alternativeAdminUser);
    $membership
      ->addRole($this->ogAlternativeAdminRole)
      ->save();
  }

  /**
   * Test access to an arbitrary permission.
   *
   * @covers ::userAccess
   */
  public function testUserAccessArbitraryPermissions() {
    [$roles, $users] = $this->setupUserAccessArbitraryPermissions();

    /** @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = $this->container->get('og.access');

    // Check the user that has an arbitrary permission in both groups. It should
    // have permission to the permission in group 1.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $users['has_permission_in_both_groups'])->isAllowed());
    // This user should not have access to 'some_perm_2' as that was only
    // assigned to group 2.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm_2', $users['has_permission_in_both_groups'])->isForbidden());
    // Check the permission of group 1 again.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $users['has_permission_in_both_groups'])->isAllowed());

    // A member user without the correct role.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $users['has_no_permission'])->isForbidden());

    // A non-member user.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isForbidden());

    // Grant the arbitrary permission to non-members and check that our
    // non-member now has the permission.
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::loadByGroupAndName($this->group, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('some_perm')
      ->save();
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isAllowed());

    // Revoke the arbitrary permission again for non-members and check that our
    // poor non-member loses the permission.
    $role
      ->revokePermission('some_perm')
      ->save();
    $this->assertFalse($og_access->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isAllowed());

    // Make the non-member a member with the role. They should regain the
    // permission.
    $membership = Og::createMembership($this->group, $this->nonMemberUser);
    $membership
      ->addRole($roles['arbitrary_permission'])
      ->save();
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $this->nonMemberUser)->isAllowed());

    // Group admin user should have access regardless.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertTrue($og_access->userAccess($this->group, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // Also group admins that have a custom admin role should have access.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $this->alternativeAdminUser)->isAllowed());
    $this->assertTrue($og_access->userAccess($this->group, $this->randomMachineName(), $this->alternativeAdminUser)->isAllowed());

    // The admin user should no longer have access if the role is demoted from
    // being an admin role.
    $this->ogAdminRole->setIsAdmin(FALSE)->save();
    $this->assertFalse($og_access->userAccess($this->group, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertFalse($og_access->userAccess($this->group, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // The group owner should have access using the default configuration.
    $this->assertTrue($og_access->userAccess($this->group, 'some_perm', $this->ownerUser)->isAllowed());

    // Change the configuration to no longer grant full access to the group
    // owner. This should revoke access.
    $this->config('og.settings')->set('group_manager_full_access', FALSE)->save();
    $this->assertFalse($og_access->userAccess($this->group, 'some_perm', $this->ownerUser)->isAllowed());
  }

  /**
   * Sets up a matrix of users that have arbitrary permissions.
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
    $role = OgRole::create();
    $role
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm')
      ->save();
    $roles['arbitrary_permission'] = $role;

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
    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = Og::createMembership($this->group, $user);
    $membership
      ->addRole($role)
      ->save();

    $membership = Og::createMembership($alternate_group, $user);
    $membership
      ->addRole($alternate_role)
      ->save();

    $users['has_permission_in_both_groups'] = $user;

    // Create a user which is a member but has no special permissions.
    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    $membership = Og::createMembership($this->group, $user);
    $membership
      ->addRole($this->ogRoleWithoutPermission)
      ->save();

    $users['has_no_permission'] = $user;

    return [$roles, $users];
  }

}
