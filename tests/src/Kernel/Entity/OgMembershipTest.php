<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
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
    'og',
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
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create a bundle and add as a group.
    $group = EntityTest::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
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
   * Tests getting and setting users on OgMemberships.
   *
   * @covers ::getUser
   * @covers ::setUser
   */
  public function testGetSetUser() {
    $membership = Og::createMembership($this->group, $this->user);
    $membership->save();

    // Check the user is returned.
    $this->assertInstanceOf(UserInterface::class, $membership->getUser());
    $this->assertEquals($this->user->id(), $membership->getUser()->id());

    // And after re-loading.
    $membership = $this->entityTypeManager->getStorage('og_membership')->loadUnchanged($membership->id());

    $this->assertInstanceOf(UserInterface::class, $membership->getUser());
    $this->assertEquals($this->user->id(), $membership->getUser()->id());
  }

  /**
   * Tests getting an ogMembership from the static cache.
   */
  public function testMembershipStaticCache() {
    // Create a second bundle and add as a group.
    $another_group = EntityTest::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
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
    /** @var OgMembershipInterface $membership */
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
    /** @var OgMembershipInterface $membership */
    $membership = OgMembership::create();
    $membership
      ->setUser($this->user)
      ->save();
  }

  /**
   * Tests saving a membership with a non group entity.
   *
   * @covers ::preSave
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   */
  public function testSetNonValidGroupException() {
    $non_group = EntityTest::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $non_group->save();
    /** @var OgMembershipInterface $membership */
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
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $group->save();

    Og::groupTypeManager()->addGroup('entity_test', $group->bundle());

    /** @var OgMembershipInterface $membership */
    $membership1 = Og::createMembership($group, $this->user);
    $membership1->save();

    $membership2 = Og::createMembership($group, $this->user);
    $membership2->save();
  }

  /**
   * Tests re-saving a membership.
   *
   * @covers ::preSave
   */
  public function testSaveSameMembershipTwice() {
    $group = EntityTest::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $group->save();

    Og::groupTypeManager()->addGroup('entity_test', $group->bundle());

    /** @var OgMembershipInterface $membership */
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
