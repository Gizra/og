<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests the OgMembership entity.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Entity\OgMembership
 */
class OgMembershipTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

  /**
   * Test group.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $group;

  /**
   * Test group owner.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $owner;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->groupTypeManager = $this->container->get('og.group_type_manager');

    $storage = $this->entityTypeManager->getStorage('user');
    // Insert a row for the anonymous user.
    // @see user_install().
    $storage->create(['uid' => 0, 'status' => 0, 'name' => ''])->save();

    // Create the group owner.
    $owner = User::create(['name' => $this->randomString()]);
    $owner->save();
    $this->owner = $owner;

    // Create a bundle and add as a group.
    $group = EntityTest::create([
      'type' => 'test_bundle',
      'name' => $this->randomString(),
      'user_id' => $owner->id(),
    ]);

    $group->save();
    $this->group = $group;

    // Add that as a group.
    $this->groupTypeManager->addGroup('entity_test', $group->bundle());

    // Create test user.
    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    $this->user = $user;
  }

  /**
   * Tests getting and setting owners on OgMemberships.
   *
   * @covers ::getOwner
   * @covers ::getOwnerId
   * @covers ::setOwner
   */
  public function testGetSetOwner() {
    $membership = OgMembership::create();
    $membership
      ->setOwner($this->user)
      ->setGroup($this->group)
      ->save();

    $this->assertOwner($membership);
  }

  /**
   * Tests getting the owner of a newly created membership.
   *
   * @covers ::getOwner
   */
  public function testGetOwnerOnNewMembership() {
    // A brand new entity does not have an owner set yet. It should throw a
    // logic exception.
    $membership = OgMembership::create();
    $this->expectException(\LogicException::class);
    $membership->getOwner();
  }

  /**
   * Tests getting the owner ID of a newly created membership.
   *
   * @covers ::getOwnerId
   */
  public function testGetOwnerIdOnNewMembership() {
    // A brand new entity does not have an owner set yet. It should throw a
    // logic exception.
    $membership = OgMembership::create();
    $this->expectException(\LogicException::class);
    $membership->getOwnerId();
  }

  /**
   * Tests getting and setting owners by ID on OgMemberships.
   *
   * @covers ::getOwner
   * @covers ::getOwnerId
   * @covers ::setOwnerId
   */
  public function testGetSetOwnerId() {
    $membership = OgMembership::create();
    $membership
      ->setOwnerId($this->user->id())
      ->setGroup($this->group)
      ->save();

    $this->assertOwner($membership);
  }

  /**
   * Asserts that the test user is set as the owner of the given membership.
   *
   * @param \Drupal\og\OgMembershipInterface $membership
   *   The membership to check.
   */
  protected function assertOwner(OgMembershipInterface $membership) {
    // Check the user is returned.
    $this->assertInstanceOf(UserInterface::class, $membership->getOwner());
    $this->assertEquals($this->user->id(), $membership->getOwnerId());

    // And after re-loading.
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entityTypeManager->getStorage('og_membership')->loadUnchanged($membership->id());

    $this->assertInstanceOf(UserInterface::class, $membership->getOwner());
    $this->assertEquals($this->user->id(), $membership->getOwnerId());
  }

  /**
   * Tests getting an ogMembership from the static cache.
   */
  public function testMembershipStaticCache() {
    // Create a second bundle and add as a group.
    $another_group = EntityTest::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $another_group->save();
    $this->groupTypeManager->addGroup('entity_test', $another_group->bundle());

    $membership = Og::createMembership($this->group, $this->user);
    $membership->save();
    // Load the membership to instantiate the membership static cache.
    $membership = Og::getMembership($this->group, $this->user);
    $this->assertInstanceOf(OgMembership::class, $membership);

    // Create another membership for the given user on the same request.
    $membership = Og::createMembership($another_group, $this->user);
    $membership->save();
    $membership = Og::getMembership($another_group, $this->user);
    $this->assertInstanceOf(OgMembership::class, $membership);
  }

  /**
   * Tests exceptions are thrown when trying to save a membership with no user.
   *
   * @covers ::preSave
   */
  public function testSetNoUserException() {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $this->expectException(EntityStorageException::class);
    $membership
      ->setGroup($this->group)
      ->save();
  }

  /**
   * Tests exceptions are thrown when trying to save a membership with no group.
   *
   * @covers ::preSave
   */
  public function testSetNoGroupException() {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create();
    $this->expectException(EntityStorageException::class);
    $membership
      ->setOwner($this->user)
      ->save();
  }

  /**
   * Test that it is possible to create groups without an owner.
   *
   * @todo This test is not related to the OgMembership entity. It should be
   *   moved to a more appropriate test class.
   */
  public function testNoOwnerException() {
    // Create a bundle and add as a group.
    $bundle = mb_strtolower($this->randomMachineName());
    $group = NodeType::create([
      'type' => $bundle,
      'label' => $this->randomString(),
    ]);
    $group->save();

    // Add that as a group.
    $this->groupTypeManager->addGroup('node', $bundle);
    $entity = Node::create([
      'title' => $this->randomString(),
      'type' => $bundle,
    ]);
    $entity->save();
  }

  /**
   * Tests saving a membership with a non group entity.
   *
   * @covers ::preSave
   */
  public function testSetNonValidGroupException() {
    $non_group = EntityTest::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $non_group->save();
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = Og::createMembership($non_group, $this->user);

    $this->expectException(EntityStorageException::class);
    $membership->save();
  }

  /**
   * Tests saving an existing membership.
   *
   * @covers ::preSave
   */
  public function testSaveExistingMembership() {
    $group = EntityTest::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $group->save();

    $this->groupTypeManager->addGroup('entity_test', $group->bundle());

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership1 = Og::createMembership($group, $this->user);
    $membership1->save();

    $membership2 = Og::createMembership($group, $this->user);

    $this->expectException(EntityStorageException::class);
    $membership2->save();
  }

  /**
   * Tests saving a membership with a role with a different group type.
   *
   * @covers ::preSave
   * @dataProvider saveRoleWithWrongGroupTypeProvider
   */
  public function testSaveRoleWithWrongGroupType($group_entity_type_id, $group_bundle_id) {
    $group = EntityTest::create([
      'type' => 'entity_test',
      'name' => $this->randomString(),
    ]);

    $group->save();

    $this->groupTypeManager->addGroup('entity_test', $group->bundle());

    $wrong_role = OgRole::create()
      ->setGroupType($group_entity_type_id)
      ->setGroupBundle($group_bundle_id)
      ->setName(mb_strtolower($this->randomMachineName()));
    $wrong_role->save();

    $this->expectException(EntityStorageException::class);
    Og::createMembership($group, $this->user)->addRole($wrong_role)->save();
  }

  /**
   * Data provider for testSaveRoleWithWrongGroupType().
   *
   * @return array
   *   An array of test data, each item an array consisting of two items:
   *   1. The entity type ID of the role to add to the membership.
   *   2. The bundle ID of the role to add to the membership.
   */
  public function saveRoleWithWrongGroupTypeProvider() {
    return [
      // Try saving a membership containing a role with the wrong entity type.
      [
        'user',
        'entity_test',
      ],
      // Try saving a membership containing a role with the wrong bundle.
      [
        'entity_test',
        'some_other_bundle',
      ],
    ];
  }

  /**
   * Tests if it is possible to check if a role is valid for a membership.
   *
   * @covers ::isRoleValid
   * @dataProvider isRoleValidProvider
   */
  public function testIsRoleValid($group_type, $group_bundle, $role_name, $expected) {
    $role = OgRole::create([
      'group_type' => $group_type,
      'group_bundle' => $group_bundle,
      'name' => $role_name,
    ]);

    $group = EntityTest::create([
      'type' => 'entity_test',
      'name' => $this->randomString(),
    ]);
    $group->save();

    $membership = OgMembership::create()->setGroup($group);

    $this->assertEquals($expected, $membership->isRoleValid($role));
  }

  /**
   * Data provider for testIsRoleValid().
   *
   * @return array
   *   An array of test data, each test case containing the following 4 items:
   *   1. The entity type ID of the role.
   *   2. The bundle ID of the role.
   *   3. The role name.
   *   4. A boolean indicating whether or not this role is expected to be valid.
   */
  public function isRoleValidProvider() {
    return [
      // A valid role.
      [
        'entity_test',
        'entity_test',
        'administrator',
        TRUE,
      ],
      // An invalid role which has the wrong group entity type.
      [
        'user',
        'entity_test',
        'administrator',
        FALSE,
      ],
      // An invalid role which has the wrong group bundle.
      [
        'entity_test',
        'incorrect_bundle',
        'administrator',
        FALSE,
      ],
      // A non-member role is never valid for any membership.
      [
        'entity_test',
        'entity_test',
        OgRoleInterface::ANONYMOUS,
        FALSE,
      ],
    ];
  }

  /**
   * Tests an exception is thrown when saving a membership with invalid roles.
   *
   * @param array $roles_metadata
   *   An array of test role metadata.
   *
   * @covers ::save
   * @dataProvider saveMembershipWithInvalidRolesProvider
   */
  public function testSaveMembershipWithInvalidRoles(array $roles_metadata): void {
    $test_groups = ['test_group' => $this->group];

    // Create a second test group that has the same entity type but a different
    // bundle so we can test that roles created for this group will throw an
    // exception.
    $group = EntityTest::create([
      'type' => 'a_different_group_bundle',
      'name' => $this->randomString(),
    ]);
    $group->save();
    $this->groupTypeManager->addGroup($group->getEntityTypeId(), $group->bundle());
    $test_groups['different_bundle'] = $group;

    // Create a third test group that has a different entity type but the same
    // bundle so we can test that roles created for this group will throw an
    // exception.
    $group = EntityTestMul::create([
      'type' => 'test_bundle',
      'name' => $this->randomString(),
    ]);
    $group->save();
    $this->groupTypeManager->addGroup($group->getEntityTypeId(), $group->bundle());
    $test_groups['different_entity_type'] = $group;

    // Create the roles as defined in the test case.
    $roles = [];
    foreach ($roles_metadata as $role_metadata) {
      $group = $test_groups[$role_metadata['group']];
      $role = OgRole::loadByGroupAndName($group, $role_metadata['role_name']);

      // Create the role if it was not created automatically.
      if (empty($role)) {
        $role = OgRole::create([
          'group_type' => $group->getEntityTypeId(),
          'group_bundle' => $group->bundle(),
          'name' => $role_metadata['role_name'],
        ]);
        $role->save();
      }
      $roles[] = $role;
    }

    // Create a membership with the test group and roles. This should throw an
    // exception since the roles are invalid.
    $this->expectException(EntityStorageException::class);
    OgMembership::create()
      ->setOwner($this->user)
      ->setGroup($this->group)
      ->setRoles($roles)
      ->save();
  }

  /**
   * Provides test data for saving a membership with invalid roles.
   *
   * @return array
   *   An array of test data, each item is an associative array of role metadata
   *   with the following keys:
   *   - group: the group to associate with the role. Can be 'test_group',
   *     'different_bundle', or 'different_entity_type'.
   *   - role_name: the role name.
   */
  public function saveMembershipWithInvalidRolesProvider(): array {
    return [
      // A membership can not be saved for an anonymous user.
      [
        [
          [
            'group' => 'test_group',
            'role_name' => OgRoleInterface::ANONYMOUS,
          ],
        ],
      ],
      // A membership with multiple roles can not be saved if any of the roles
      // is for an anonymous user.
      [
        [
          [
            'group' => 'test_group',
            'role_name' => OgRoleInterface::ADMINISTRATOR,
          ],
          [
            'group' => 'test_group',
            'role_name' => 'custom_role',
          ],
          [
            'group' => 'test_group',
            'role_name' => OgRoleInterface::ANONYMOUS,
          ],
        ],
      ],
      // A membership can not be saved when one of the roles references a
      // different bundle.
      [
        [
          [
            'group' => 'test_group',
            'role_name' => OgRoleInterface::ADMINISTRATOR,
          ],
          [
            'group' => 'different_bundle',
            'role_name' => OgRoleInterface::ADMINISTRATOR,
          ],
        ],
      ],
      // A membership can not be saved when one of the roles references a
      // different entity type.
      [
        [
          [
            'group' => 'test_group',
            'role_name' => OgRoleInterface::ADMINISTRATOR,
          ],
          [
            'group' => 'different_entity_type',
            'role_name' => OgRoleInterface::ADMINISTRATOR,
          ],
        ],
      ],
    ];
  }

  /**
   * Tests the exception thrown if the validity of a role cannot be established.
   *
   * @covers ::isRoleValid
   */
  public function testIsRoleValidException() {
    $role = OgRole::create([
      'group_type' => 'entity_test',
      'group_bundle' => 'entity_test',
    ]);
    $membership = OgMembership::create();

    // If a membership doesn't have a group yet it is not possible to determine
    // wheter a role is valid.
    $this->expectException(\LogicException::class);
    $membership->isRoleValid($role);
  }

  /**
   * Tests re-saving a membership.
   *
   * @covers ::preSave
   */
  public function testSaveSameMembershipTwice() {
    $group = EntityTest::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $group->save();

    $this->groupTypeManager->addGroup('entity_test', $group->bundle());

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = Og::createMembership($group, $this->user);
    $membership->save();

    // Block membership and save.
    $membership->setState(OgMembershipInterface::STATE_BLOCKED);
    $membership->save();
  }

  /**
   * Tests boolean check for states.
   *
   * @covers ::isActive
   * @covers ::isPending
   * @covers ::isBlocked
   * @dataProvider statesProvider
   */
  public function testGetSetState($state, $method) {
    $membership = Og::createMembership($this->group, $this->user);
    $membership->setState($state)->save();

    $membership = $this->entityTypeManager->getStorage('og_membership')->loadUnchanged($membership->id());

    $this->assertEquals($state, $membership->getState());
    $this->assertTrue($membership->$method());
  }

  /**
   * Tests getting the group that is associated with a membership.
   *
   * @covers ::getGroup
   */
  public function testGetGroup() {
    $membership = OgMembership::create();

    // Set a group.
    $membership->setGroup($this->group);

    // Now the group should be returned. Check both the entity type and ID.
    $this->assertEquals($this->group->getEntityTypeId(), $membership->getGroup()->getEntityTypeId());
    $this->assertEquals($this->group->id(), $membership->getGroup()->id());
  }

  /**
   * Tests getting the group from a new membership.
   *
   * @covers ::getGroup
   */
  public function testGetGroupOnNewMembership() {
    $membership = OgMembership::create();

    // When no group has been set yet, the method should throw an assertion.
    $this->expectException(\LogicException::class);
    $membership->getGroup();
  }

  /**
   * Tests getting the bundle of the group that is associated with a membership.
   *
   * @covers ::getGroupBundle
   */
  public function testGetGroupBundle() {
    $membership = OgMembership::create();

    // Set a group.
    $membership->setGroup($this->group);

    // Now the group bundle should be returned.
    $this->assertEquals($this->group->bundle(), $membership->getGroupBundle());
  }

  /**
   * Tests getting the group bundle of a newly created membership.
   *
   * @covers ::getGroupBundle
   */
  public function testGetGroupBundleOnNewMembership() {
    $membership = OgMembership::create();

    $this->expectException(\LogicException::class);
    $membership->getGroupBundle();
  }

  /**
   * Tests getting the entity type ID of the group associated with a membership.
   *
   * @covers ::getGroupEntityType
   */
  public function testGetGroupEntityType() {
    $membership = OgMembership::create();
    $membership->setGroup($this->group);
    $this->assertEquals($this->group->getEntityTypeId(), $membership->getGroupEntityType());
  }

  /**
   * Tests getting the group entity type ID of a newly created membership.
   *
   * @covers ::getGroupEntityType
   */
  public function testGetGroupEntityTypeOnNewMembership() {
    $membership = OgMembership::create();

    $this->expectException(\LogicException::class);
    $membership->getGroupEntityType();
  }

  /**
   * Tests getting the ID of the group associated with a membership.
   *
   * @covers ::getGroupId
   */
  public function testGetGroupId() {
    $membership = OgMembership::create();
    $membership->setGroup($this->group);
    $this->assertEquals($this->group->id(), $membership->getGroupId());
  }

  /**
   * Tests getting the group ID of a newly created membership.
   *
   * @covers ::getGroupId
   */
  public function testGetGroupIdOnNewMembership() {
    $membership = OgMembership::create();

    $this->expectException(\LogicException::class);
    $membership->getGroupId();
  }

  /**
   * Tests getting and setting the creation time.
   *
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   */
  public function testGetSetCreatedTime() {
    // When creating a brand new membership the request time should be set as
    // the creation time.
    // @todo Replace this with \Drupal::time()->getRequestTime() in Drupal 9.
    $expected_time = (int) $_SERVER['REQUEST_TIME'];
    $membership = OgMembership::create();
    $this->assertEquals($expected_time, $membership->getCreatedTime());

    // Try setting a custom creation time and retrieving it.
    $custom_time = strtotime('January 1, 2019');
    $created_time = $membership
      ->setCreatedTime($custom_time)
      ->getCreatedTime();
    $this->assertEquals($custom_time, $created_time);
  }

  /**
   * Tests that membership has "member" role when roles are retrieved.
   *
   * @covers ::getRoles
   */
  public function testMemberRole() {
    $membership = Og::createMembership($this->group, $this->user);
    $membership->setState(OgMembershipInterface::STATE_ACTIVE)->save();

    $membership = $this->entityTypeManager->getStorage('og_membership')->loadUnchanged($membership->id());

    $roles = $membership->getRoles();
    $role = current($roles);

    $this->assertEquals(1, count($roles));
    $this->assertEquals(OgRoleInterface::AUTHENTICATED, $role->getName());
  }

  /**
   * Tests that we can retrieve the (empty) roles list from a new membership.
   *
   * If a membership is newly created and doesn't have a group associated with
   * it yet, it should still be possible to get the (empty) list of roles
   * without getting any errors.
   *
   * @covers ::getRoles
   */
  public function testGetRolesFromMembershipWithoutGroup() {
    $membership = OgMembership::create();
    $roles = $membership->getRoles();
    $this->assertEquals([], $roles);
  }

  /**
   * Tests that the role ids are being built properly by the membership.
   *
   * @covers ::getRolesIds
   */
  public function testGetRolesIdsFromMembership() {
    $entity_type_id = $this->group->getEntityTypeId();
    $bundle = $this->group->bundle();

    $og_extra_role = OgRole::create()
      ->setGroupType($entity_type_id)
      ->setGroupBundle($bundle)
      ->setName(mb_strtolower($this->randomMachineName()));
    $og_extra_role->save();

    $membership = OgMembership::create()
      ->setGroup($this->group)
      ->setOwner($this->user)
      ->addRole($og_extra_role);
    $membership->save();

    $role_names = ['member', $og_extra_role->getName()];
    $expected_ids = array_map(function ($role_name) use ($entity_type_id, $bundle) {
      return "{$entity_type_id}-{$bundle}-{$role_name}";
    }, $role_names);
    $actual_ids = $membership->getRolesIds();

    // Sort the two arrays before comparing so we can check the contents
    // regardless of their order.
    sort($expected_ids);
    sort($actual_ids);

    $this->assertEquals($expected_ids, $actual_ids, 'Role ids are built properly.');
  }

  /**
   * Tests that the membership can return if it belongs to the group owner.
   *
   * @covers ::isOwner
   */
  public function testIsOwner() {
    // Check the membership of the group owner.
    $membership = Og::createMembership($this->group, $this->owner);
    $this->assertTrue($membership->isOwner());

    // Check the membership of a normal user.
    $membership = Og::createMembership($this->group, $this->user);
    $this->assertFalse($membership->isOwner());
  }

  /**
   * Provides test data to check states.
   *
   * @return array
   *   Array with the state names and the method to check their flags.
   */
  public function statesProvider() {
    return [
      [OgMembershipInterface::STATE_ACTIVE, 'isActive'],
      [OgMembershipInterface::STATE_PENDING, 'isPending'],
      [OgMembershipInterface::STATE_BLOCKED, 'isBlocked'],
    ];
  }

}
