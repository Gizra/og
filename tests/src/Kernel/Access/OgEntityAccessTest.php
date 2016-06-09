<?php

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\og\OgMembershipInterface;
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
  public static $modules = ['system', 'user', 'field', 'og', 'entity_test'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user1;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user3;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group1;

  /**
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
   * @var OgRole
   */
  protected $ogRoleWithPermission;

  /**
   * The OG role that has the permission we check for.
   *
   * @var OgRole
   */
  protected $ogRoleWithPermission2;

  /**
   * The OG role that doesn't have the permission we check for.
   *
   * @var OgRole
   */
  protected $ogRoleWithoutPermission;

  /**
   * The OG role that doesn't have the permission we check for.
   *
   * @var OgRole
   */
  protected $ogAdminRole;

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

    $this->groupBundle = Unicode::strtolower($this->randomMachineName());


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

    // Admin user.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->save();


    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

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

    /** @var OgRole ogRoleWithPermission */
    $this->ogRoleWithPermission = OgRole::create();
    $this->ogRoleWithPermission
      ->setId($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm')
      ->save();

    $this->ogRoleWithPermission2 = OgRole::create();
    $this->ogRoleWithPermission2
      ->setId($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm_2')
      ->save();

    /** @var OgRole ogRoleWithoutPermission */
    $this->ogRoleWithoutPermission = OgRole::create();
    $this->ogRoleWithoutPermission
      ->setId($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->grantPermission($this->randomMachineName())
      ->save();

    $this->ogAdminRole = OgRole::create();
    $this->ogAdminRole
      ->setId($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($this->group1->getEntityTypeId())
      ->setGroupBundle($this->groupBundle)
      ->setIsAdmin(TRUE)
      ->save();


    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user1->id())
      ->setEntityId($this->group1->id())
      ->setGroupEntityType($this->group1->getEntityTypeId())
      ->addRole($this->ogRoleWithPermission->id())
      ->save();

    // Also create a membership to the other group. From this we can verify that
    // permissions are not bled between groups.
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user1->id())
      ->setEntityId($this->group2->id())
      ->setGroupEntityType($this->group2->getEntityTypeId())
      ->addRole($this->ogRoleWithPermission2->id())
      ->save();

    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user2->id())
      ->setEntityId($this->group1->id())
      ->setGroupEntityType($this->group1->getEntityTypeId())
      ->addRole($this->ogRoleWithoutPermission->id())
      ->save();

    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->adminUser->id())
      ->setEntityId($this->group1->id())
      ->setGroupEntityType($this->group1->getEntityTypeId())
      ->addRole($this->ogAdminRole->id())
      ->save();
  }

  /**
   * Test access to an arbitrary permission.
   */
  public function testAccess() {
    // A member user.
    $this->assertTrue(OgAccess::userAccess($this->group1, 'some_perm', $this->user1)->isAllowed());
    // This user should not have access to 'some_perm_2' as that was only
    // assigned to group 2.
    $this->assertTrue(OgAccess::userAccess($this->group1, 'some_perm_2', $this->user1)->isForbidden());

    // A member user without the correct role.
    $this->assertTrue(OgAccess::userAccess($this->group1, 'some_perm', $this->user2)->isForbidden());

    // A non-member user.
    $this->assertTrue(OgAccess::userAccess($this->group1, 'some_perm', $this->user3)->isForbidden());

    // Group admin user should have access regardless.
    $this->assertTrue(OgAccess::userAccess($this->group1, 'some_perm', $this->adminUser)->isAllowed());
    $this->assertTrue(OgAccess::userAccess($this->group1, $this->randomMachineName(), $this->adminUser)->isAllowed());

    // Add membership to user 3.
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user3->id())
      ->setEntityId($this->group1->id())
      ->setGroupEntityType($this->group1->getEntityTypeId())
      ->addRole($this->ogRoleWithPermission->id())
      ->save();

    $this->assertTrue(OgAccess::userAccess($this->group1, 'some_perm', $this->user3)->isAllowed());
  }


}
