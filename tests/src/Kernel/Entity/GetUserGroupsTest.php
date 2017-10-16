<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Tests getting the memberships of an entity.
 *
 * @group og
 */
class GetUserGroupsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'og',
    'options',
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
    Og::groupTypeManager()->addGroup('entity_test', $this->groupBundle);

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
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    $actual = $membership_manager->getUserGroups($this->user1);

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);

    // Also check isMember.
    $this->assertTrue(Og::isMember($this->group1, $this->user1));
    $this->assertFalse(Og::isMember($this->group1, $this->user2));

    $actual = $membership_manager->getUserGroups($this->user2);

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
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');

    // Should not be a part of any groups.
    $this->assertEquals([], $membership_manager->getUserGroups($this->user3));
    $this->assertFalse(Og::isMember($this->group1, $this->user3));
    $this->assertFalse(Og::isMember($this->group2, $this->user3));

    // Invalidate the caches so the static cache is cleared and group data is
    // fetched again for the user.
    Og::invalidateCache();

    // Add user to group 1 should now return that group only.
    $this->createMembership($this->user3, $this->group1);

    $actual = $membership_manager->getUserGroups($this->user3);

    $this->assertCount(1, $actual['entity_test']);
    $this->assertGroupExistsInResults($this->group1, $actual);

    $this->assertTrue(Og::isMember($this->group1, $this->user3));
    $this->assertFalse(Og::isMember($this->group2, $this->user3));

    Og::invalidateCache();

    // Add to group 2 should also return that.
    $this->createMembership($this->user3, $this->group2);

    $actual = $membership_manager->getUserGroups($this->user3);

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
   *   The user object to create membership for.
   * @param \Drupal\entity_test\Entity\EntityTest $group
   *   The entity to create the membership for.
   * @param int $state
   *   The state of the membership.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The saved OG membership entity.
   */
  protected function createMembership(User $user, EntityTest $group, $state = OgMembershipInterface::STATE_ACTIVE) {
    $membership = Og::createMembership($group, $user);
    $membership
      ->setState($state)
      ->save();

    return $membership;
  }

  /**
   * Asserts whether a group ID exists in some results.
   *
   * Assumes entity_type is used.
   *
   * @param \Drupal\entity_test\Entity\EntityTest $group_to_check
   *   The group entity to check.
   * @param array $results
   *   Array keyed by the entity type, and with the group entities as values.
   */
  protected function assertGroupExistsInResults(EntityTest $group_to_check, array $results) {
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
