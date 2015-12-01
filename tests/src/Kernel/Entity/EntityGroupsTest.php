<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\EntityGroupsTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\user\Entity\User;

/**
 * Tests entity group methods
 *
 * @group og
 */
class EntityGroupsTest extends KernelTestBase {

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
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group1;

  /**
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group2;

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The machine name of the group content node type.
   *
   * @var string
   */
  protected $groupContentBundle;

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
    $this->groupContentBundle = Unicode::strtolower($this->randomMachineName());

    // Create users.
    $this->user1 = User::create(['name' => $this->randomString()]);
    $this->user1->save();

    $this->user2 = User::create(['name' => $this->randomString()]);
    $this->user2->save();

    $this->user3 = User::create(['name' => $this->randomString()]);
    $this->user3->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with user 1.
    $this->group1 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->user1->id(),
    ]);
    $this->group1->save();

    // Create a group and associate with user 2.
    $this->group2 = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->user2->id(),
    ]);
    $this->group2->save();
  }

  /**
   * Tests group owners have the correct groups.
   */
  public function testOwnerGroupsOnly() {
    $actual = Og::getEntityGroups('user', $this->user1->id());

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);

    // Also check isMember.
    $this->assertTrue(Og::isMember('entity_test', $this->group1->id(), 'user', $this->user1->id()));
    $this->assertFalse(Og::isMember('entity_test', $this->group1->id(), 'user', $this->user2->id()));

    $actual = Og::getEntityGroups('user', $this->user2->id());

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group2, $actual);

    // Also check isMember.
    $this->assertTrue(Og::isMember('entity_test', $this->group2->id(), 'user', $this->user2->id()));
    $this->assertFalse(Og::isMember('entity_test', $this->group2->id(), 'user', $this->user1->id()));
  }

  /**
   * Tests other groups users are added to.
   */
  public function testOtherGroups() {
    // Should be a part of no groups.
    $this->assertEquals([], Og::getEntityGroups('user', $this->user3->id()));
    $this->assertFalse(Og::isMember('entity_test', $this->group1->id(), 'user', $this->user3->id()));
    $this->assertFalse(Og::isMember('entity_test', $this->group2->id(), 'user', $this->user3->id()));

    // Invalidate the caches so the static cache is cleared and group data is
    // fetched again for the user.
    Og::invalidateCache();

    // Add user to group 1 should now return that group only.
    $this->createMembership($this->user3, $this->group1);

    $actual = Og::getEntityGroups('user', $this->user3->id());

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);

    $this->assertTrue(Og::isMember('entity_test', $this->group1->id(), 'user', $this->user3->id()));
    $this->assertFalse(Og::isMember('entity_test', $this->group2->id(), 'user', $this->user3->id()));

    Og::invalidateCache();

    // Add to group 2 should also return that.
    $this->createMembership($this->user3, $this->group2);

    $actual = Og::getEntityGroups('user', $this->user3->id());

    $this->assertCount(2, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);
    $this->assertGroupExistsInResults($this->group2, $actual);

    $this->assertTrue(Og::isMember('entity_test', $this->group1->id(), 'user', $this->user3->id()));
    $this->assertTrue(Og::isMember('entity_test', $this->group2->id(), 'user', $this->user3->id()));
  }

  /**
   * Creates an Og membership entity.
   *
   * @todo This is a temp function, which will be later replaced by Og::group().
   *
   * @param \Drupal\user\Entity\User $user
   * @param \Drupal\Core\Entity\EntityInterface $group
   *
   * @return \Drupal\og\Entity|OgMembership
   */
  protected function createMembership($user, $group) {
    $membership = OgMembership::create(['type' => OG_MEMBERSHIP_TYPE_DEFAULT])
      ->setEntityId($user->id())
      ->setEntityType('user')
      ->setGid($group->id())
      ->setGroupType($group->getEntityTypeId())
      ->save();

    return $membership;
  }

  /**
   * Asserts whether a group ID exists in some results.
   *
   * Assumes entity_type is used.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group_to_check
   * @param array $results
   */
  protected function assertGroupExistsInResults($group_to_check, array $results) {
    $found = FALSE;
    foreach ($results['entity_test'] as $group) {
      if ($group->id() == $group_to_check->id()) {
        $found = TRUE;
        break;
      }
    }

    $this->assertTrue($found);
  }

}
