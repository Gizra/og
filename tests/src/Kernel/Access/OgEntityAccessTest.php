<?php

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;

/**
 * Test permission inside a group.
 *
 * @group og
 */
class OgEntityAccessTest extends KernelTestBase {

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
  protected $user1;

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user3;

  /**
   * An user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user4;

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
  protected $group1;

  /**
   * A group entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group2;

  /**
   * The machine name of the group's bundle.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The OG role that has the permission we check for.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $ogRoleWithPermission;

  /**
   * The OG role that has the permission we check for.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $ogRoleWithPermission2;

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
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installSchema('system', 'sequences');

    $this->groupBundle = mb_strtolower($this->randomMachineName());

    // Create users, and make sure user ID 1 isn't used.
    User::create(['name' => $this->randomString()]);

    $group_owner = User::create(['name' => $this->randomString()]);
    $group_owner->save();

    // A group member with the correct role.
    $this->user1 = User::create(['name' => $this->randomString()]);
    $this->user1->save();

    // A group member without the correct role.
    $this->user2 = User::create(['name' => $this->randomString()]);
    $this->user2->save();

    // A non-member.
    $this->user3 = User::create(['name' => $this->randomString()]);
    $this->user3->save();

    // A group member the special permission 'update group'.
    $this->user4 = User::create(['name' => $this->randomString()]);
    $this->user4->save();

    // Admin user.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();

    // Second admin user which uses an alternative administration role.
    $this->alternativeAdminUser = User::create(['name' => $this->randomString()]);
    $this->alternativeAdminUser->save();

    // Declare the test entity as being a group.
    Og::groupTypeManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with user 1.
    $this->group1 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $group_owner->id(),
    ]);
    $this->group1->save();

    // Create another group to help test per group/per account permission
    // caching.
    $this->group2 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $group_owner->id(),
    ]);
    $this->group2->save();

    $this->ogRoleWithPermission = OgRole::create();
    $this->ogRoleWithPermission
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm')
      ->save();

    $this->ogRoleWithPermission2 = OgRole::create();
    $this->ogRoleWithPermission2
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm_2')
      ->save();

    $this->ogRoleWithUpdatePermission = OgRole::create();
    $this->ogRoleWithUpdatePermission
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->grantPermission(OgAccess::UPDATE_GROUP_PERMISSION)
      ->save();

    $this->ogRoleWithoutPermission = OgRole::create();
    $this->ogRoleWithoutPermission
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->grantPermission($this->randomMachineName())
      ->save();

    // The administrator role is added automatically when the group is created.
    // @see \Drupal\og\EventSubscriber\OgEventSubscriber::provideDefaultRoles()
    $this->ogAdminRole = OgRole::loadByGroupAndName($this->group1, OgRoleInterface::ADMINISTRATOR);

    // Create a second administration role, since this is a supported use case.
    // It is possible to have multiple administration roles.
    $this->ogAlternativeAdminRole = OgRole::create();
    $this->ogAlternativeAdminRole
      ->setName($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->setIsAdmin(TRUE)
      ->save();

    /** @var \Drupal\og\OgMembership $membership */
    $membership = Og::createMembership($this->group1, $this->user1);
    $membership
      ->addRole($this->ogRoleWithPermission)
      ->save();

    // Also create a membership to the other group. From this we can verify that
    // permissions are not bled between groups.
    $membership = Og::createMembership($this->group2, $this->user1);
    $membership
      ->addRole($this->ogRoleWithPermission2)
      ->save();

    $membership = Og::createMembership($this->group1, $this->user2);
    $membership
      ->addRole($this->ogRoleWithoutPermission)
      ->save();

    // Check the special permission 'update group'.
    $membership = Og::createMembership($this->group1, $this->user4);
    $membership
      ->addRole($this->ogRoleWithUpdatePermission)
      ->save();

    $membership = Og::createMembership($this->group1, $this->adminUser);
    $membership
      ->addRole($this->ogAdminRole)
      ->save();

    $membership = Og::createMembership($this->group1, $this->alternativeAdminUser);
    $membership
      ->addRole($this->ogAlternativeAdminRole)
      ->save();
  }

  /**
   * Test access to an arbitrary permission.
   */
  public function testAccess() {
    /** @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = $this->container->get('og.access');

    // A member user.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user1)->isAllowed());
    // This user should not have access to 'some_perm_2' as that was only
    // assigned to group 2.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm_2', $this->user1)->isForbidden());

    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user1)->isAllowed());

    // A member user without the correct role.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user2)->isForbidden());

    // A non-member user.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user3)->isForbidden());

    // Allow the permission to a non-member user.
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::loadByGroupAndName($this->group1, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('some_perm')
      ->save();

    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user3)->isAllowed());

    // A member with permission to update the group. The operation edit is
    // passed to the userAccess method.
    $this->assertTrue($og_access->userAccess($this->group1, 'edit', $this->user4)->isAllowed());

    // Group admin user should have access regardless.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertTrue($og_access->userAccess($this->group1, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // Also group admins that have a custom admin role should have access.
    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->alternativeAdminUser)->isAllowed());
    $this->assertTrue($og_access->userAccess($this->group1, $this->randomMachineName(), $this->alternativeAdminUser)->isAllowed());

    // The admin user should no longer have access if the role is demoted from
    // being an admin role.
    $this->ogAdminRole->setIsAdmin(FALSE)->save();
    $this->assertFalse($og_access->userAccess($this->group1, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertFalse($og_access->userAccess($this->group1, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // Add membership to user 3.
    $membership = Og::createMembership($this->group1, $this->user3);
    $membership
      ->addRole($this->ogRoleWithPermission)
      ->save();

    $this->assertTrue($og_access->userAccess($this->group1, 'some_perm', $this->user3)->isAllowed());
  }

}
