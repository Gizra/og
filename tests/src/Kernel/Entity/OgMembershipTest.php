<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
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

}
