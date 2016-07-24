<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
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

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface entityTypeManager */
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create a bundle and add as a group
    $group = EntityTest::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);

    $group->save();
    $this->group = $group;

    // Add that as a group.
    Og::groupManager()->addGroup('entity_test', $group->id());

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
    $membership = OgMembership::create();
    $membership
      ->setUser($this->user)
      ->setGroup($this->group)
      ->save();

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
  public function testGetSetUserException() {
    /** @var OgMembership $membership */
    $membership = OgMembership::create();
    $membership
      ->setGroup($this->group)
      ->save();
  }

}
