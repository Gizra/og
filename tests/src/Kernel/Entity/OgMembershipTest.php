<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
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
    'options',
    'system',
    'user',
  ];

  /**
   * Test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
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
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
      'user_id' => $owner->id(),
    ]);

    $group->save();
    $this->group = $group;

    // Add that as a group.
    Og::groupTypeManager()->addGroup('entity_test', $group->bundle());

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
    Og::groupTypeManager()->addGroup('entity_test', $another_group->bundle());

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
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   */
  public function testSetNoUserException() {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setGroup($this->group)
      ->save();
  }

  /**
   * Tests exceptions are thrown when trying to save a membership with no group.
   *
   * @covers ::preSave
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   */
  public function testSetNoGroupException() {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create();
    $membership
      ->setOwner($this->user)
      ->save();
  }

  /**
   * Test that it is possible to create groups without an owner.
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
    Og::groupTypeManager()->addGroup('node', $bundle);
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
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   */
  public function testSetNonValidGroupException() {
    $non_group = EntityTest::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $non_group->save();
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = Og::createMembership($non_group, $this->user);
    $membership->save();
  }

  /**
   * Tests saving an existing membership.
   *
   * @covers ::preSave
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   */
  public function testSaveExistingMembership() {
    $group = EntityTest::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $group->save();

    Og::groupTypeManager()->addGroup('entity_test', $group->bundle());

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership1 = Og::createMembership($group, $this->user);
    $membership1->save();

    $membership2 = Og::createMembership($group, $this->user);
    $membership2->save();
  }

  /**
   * Tests saving a membership with a role with a different group type.
   *
   * @covers ::preSave
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   * @dataProvider saveRoleWithWrongGroupTypeProvider
   */
  public function testSaveRoleWithWrongGroupType($group_entity_type_id, $group_bundle_id) {
    $group = EntityTest::create([
      'type' => 'entity_test',
      'name' => $this->randomString(),
    ]);

    $group->save();

    Og::groupTypeManager()->addGroup('entity_test', $group->bundle());

    $wrong_role = OgRole::create()
      ->setGroupType($group_entity_type_id)
      ->setGroupBundle($group_bundle_id)
      ->setName(mb_strtolower($this->randomMachineName()));
    $wrong_role->save();

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
   * Tests the exception thrown if the validity of a role cannot be established.
   *
   * @covers ::isRoleValid
   * @expectedException \LogicException
   */
  public function testIsRoleValidException() {
    $role = OgRole::create([
      'group_type' => 'entity_test',
      'group_bundle' => 'entity_test',
    ]);
    $membership = OgMembership::create();

    // If a membership doesn't have a group yet it is not possible to determine
    // wheter a role is valid.
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

    Og::groupTypeManager()->addGroup('entity_test', $group->bundle());

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

    // When no group has been set yet, the method should return NULL.
    $this->assertNull($membership->getGroup());

    // Set a group.
    $membership->setGroup($this->group);

    // Now the group should be returned. Check both the entity type and ID.
    $this->assertEquals($this->group->getEntityTypeId(), $membership->getGroup()->getEntityTypeId());
    $this->assertEquals($this->group->id(), $membership->getGroup()->id());
  }

  /**
   * Tests getting the bundle of the group that is associated with a membership.
   *
   * @covers ::getGroupBundle
   */
  public function testGetGroupBundle() {
    $membership = OgMembership::create();

    // When no group has been set yet, the method should return NULL.
    $this->assertNull($membership->getGroupBundle());

    // Set a group.
    $membership->setGroup($this->group);

    // Now the group bundle should be returned.
    $this->assertEquals($this->group->bundle(), $membership->getGroupBundle());
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
