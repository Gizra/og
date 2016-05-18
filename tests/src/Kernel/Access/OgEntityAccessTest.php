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
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group1;


  /**
   * The machine name of the group's bundle.
   *
   * @var string
   */
  protected $groupBundle;


  /**
   * The OG role.
   *
   * @var OgRole
   */
  protected $ogRole;

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

    // Create users.
    $this->user1 = User::create(['name' => $this->randomString()]);
    $this->user1->save();

    $this->user2 = User::create(['name' => $this->randomString()]);
    $this->user2->save();


    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with user 1.
    $this->group1 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->user1->id(),
    ]);
    $this->group1->save();

    $group_entity_type_id = $this->group1->getEntityTypeId();

    /** @var OgRole $role */
    $role = OgRole::create();
    $role
      ->setId($this->randomMachineName())
      ->setLabel($this->randomString())
      ->setGroupType($group_entity_type_id)
      ->setGroupBundle($this->groupBundle)
      // Associate an arbitrary permission with the role.
      ->grantPermission('some_perm')
      ->save();


    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user1->id())
      ->setEntityId($this->group1->id())
      ->setGroupEntityType($group_entity_type_id)
      ->addRole($role->id())
      ->save();
  }

  public function testAccess() {
    $this->assertTrue((bool) OgAccess::userAccess($this->group1, 'some_perm', $this->user1));

    $this->assertFalse((bool) OgAccess::userAccess($this->group1, 'some_perm', $this->user2));
  }


}