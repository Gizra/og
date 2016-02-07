<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\GetEntityGroupsTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Tests getting the memberships of an entity.
 *
 * @group og
 */
class GetEntityGroupsTest extends KernelTestBase {

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
    $actual = Og::getEntityGroups($this->user1);

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);

    // Also check isMember.
    $this->assertTrue(Og::isMember($this->group1, $this->user1));
    $this->assertFalse(Og::isMember($this->group1, $this->user2));

    $actual = Og::getEntityGroups($this->user2);

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group2, $actual);

    // Also check isMember.
    $this->assertTrue(Og::isMember($this->group2, $this->user2));
    $this->assertFalse(Og::isMember($this->group2, $this->user1));
  }

  /**
   * Tests other groups users are added to.
   */
  public function testOtherGroups() {
    // Should be a part of no groups.
    $this->assertEquals([], Og::getEntityGroups($this->user3));
    $this->assertFalse(Og::isMember($this->group1, $this->user3));
    $this->assertFalse(Og::isMember($this->group2, $this->user3));

    // Invalidate the caches so the static cache is cleared and group data is
    // fetched again for the user.
    Og::invalidateCache();

    // Add user to group 1 should now return that group only.
    $this->createMembership($this->user3, $this->group1);

    $actual = Og::getEntityGroups($this->user3);

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);

    $this->assertTrue(Og::isMember($this->group1, $this->user3));
    $this->assertFalse(Og::isMember($this->group2, $this->user3));

    Og::invalidateCache();

    // Add to group 2 should also return that.
    $this->createMembership($this->user3, $this->group2);

    $actual = Og::getEntityGroups($this->user3);

    $this->assertCount(2, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);
    $this->assertGroupExistsInResults($this->group2, $actual);

    $this->assertTrue(Og::isMember($this->group1, $this->user3));
    $this->assertTrue(Og::isMember($this->group2, $this->user3));
  }

  /**
   * Tests member methods for states that other groups users are added to.
   */
  public function testIsMemberStates() {
    // Add user to group 1 should now return that group only.
    $membership = $this->createMembership($this->user3, $this->group1);

    // Default param is ACTIVE.
    $this->assertTrue(Og::isMember($this->group1, $this->user3));

    $this->assertFalse(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_PENDING]));
    $this->assertFalse(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_BLOCKED]));
    $this->assertFalse(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_PENDING, OgMembershipInterface::STATE_BLOCKED]));
    $this->assertFalse(Og::isMemberPending($this->group1, $this->user3));
    $this->assertFalse(Og::isMemberBlocked($this->group1, $this->user3));

    // Change the membership state to PENDING.
    $membership->setState(OgMembershipInterface::STATE_PENDING)->save();

    Og::invalidateCache();

    $this->assertTrue(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_PENDING]));
    $this->assertTrue(Og::isMemberPending($this->group1, $this->user3));
    $this->assertTrue(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_PENDING, OgMembershipInterface::STATE_BLOCKED]));

    $this->assertFalse(Og::isMember($this->group1, $this->user3));
    $this->assertFalse(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_BLOCKED]));
    $this->assertFalse(Og::isMemberBlocked($this->group1, $this->user3));

    // Change the membership state to BLOCKED.
    $membership->setState(OgMembershipInterface::STATE_BLOCKED)->save();

    Og::invalidateCache();

    $this->assertTrue(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_BLOCKED]));
    $this->assertTrue(Og::isMemberBlocked($this->group1, $this->user3));
    $this->assertTrue(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_PENDING, OgMembershipInterface::STATE_BLOCKED]));

    $this->assertFalse(Og::isMember($this->group1, $this->user3));
    $this->assertFalse(Og::isMember($this->group1, $this->user3, [OgMembershipInterface::STATE_PENDING]));
    $this->assertFalse(Og::isMemberPending($this->group1, $this->user3));
  }

  /**
   * Creates an Og membership entity.
   *
   * @todo This is a temp function, which will be later replaced by Og::group().
   *
   * @param \Drupal\user\Entity\User $user
   * @param \Drupal\Core\Entity\EntityInterface $group
   * @param int $state
   *
   * @return \Drupal\og\Entity|OgMembership
   */
  protected function createMembership($user, $group, $state = OgMembershipInterface::STATE_ACTIVE) {
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT])
      ->setUser($user)
      ->setEntityId($group->id())
      ->setEntityType($group->getEntityTypeId())
      ->setState($state);
    $membership->save();

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
