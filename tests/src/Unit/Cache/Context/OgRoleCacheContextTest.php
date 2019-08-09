<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Unit\Cache\Context;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\og\Cache\Context\OgRoleCacheContext;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\og\Traits\OgRoleCacheContextTestTrait;

/**
 * Tests the OG role cache context.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Cache\Context\OgRoleCacheContext
 */
class OgRoleCacheContextTest extends OgCacheContextTestBase {

  use OgRoleCacheContextTestTrait;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The mocked OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $membershipManager;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $database;

  /**
   * The mocked private key handler.
   *
   * @var \Drupal\Core\PrivateKey|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $privateKey;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->membershipManager = $this->prophesize(MembershipManagerInterface::class);
    $this->database = $this->prophesize(Connection::class);
    $this->privateKey = $this->prophesize(PrivateKey::class);
  }

  /**
   * Tests generating of a cache context key for a user with no memberships.
   *
   * This is a common case, e.g. for anonymous users.
   *
   * @covers ::getContext
   */
  public function testNoMemberships(): void {
    // No memberships (an empty array) will be returned by the membership
    // manager.
    /** @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy $user */
    $user = $this->prophesize(AccountInterface::class)->reveal();
    $this->membershipManager->getMemberships($user->id())->willReturn([]);

    // The result should be the predefined 'NO_CONTEXT' value.
    $result = $this->getContextResult($user);
    $this->assertEquals(OgRoleCacheContext::NO_CONTEXT, $result);
  }

  /**
   * Tests that no cache context key is returned if a user has lost membership.
   *
   * This can happen if for example if a user is a member with a certain role in
   * a group, and then the role is removed from config. In this case the
   * membership entity will still exist, but the user will not have any roles,
   * so no cache context key should be generated.
   *
   * @covers ::getContext
   */
  public function testMembershipsWithOrphanedRole(): void {
    // Mock the membership with the orphaned role. It will return a group and
    // group entity type, but no roles.
    /** @var \Drupal\og\OgMembershipInterface|\Prophecy\Prophecy\ObjectProphecy $membership */
    $membership = $this->prophesize(OgMembershipInterface::class);
    $membership->getRolesIds()->willReturn([]);

    // The membership with the orphaned role will be returned by the membership
    // manager.
    /** @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy $user */
    $user = $this->prophesize(AccountInterface::class)->reveal();
    $this->membershipManager->getMemberships($user->id())->willReturn([$membership]);

    // The result should be the predefined 'NO_CONTEXT' value.
    $result = $this->getContextResult($user);
    $this->assertEquals(OgRoleCacheContext::NO_CONTEXT, $result);
  }

  /**
   * Tests that the correct cache context key is returned for group members.
   *
   * Different users might have the identical roles across a number of different
   * groups. Verify that a unique hash is returned for each combination of
   * roles.
   *
   * This tests the fallback implementation for NoSQL databases. The main
   * implementation is tested in a kernel test.
   *
   * @param array $group_memberships
   *   An array that defines the roles test users have in test groups. See the
   *   data provider for a description of the format of the array.
   * @param array $expected_identical_role_groups
   *   An array containing arrays of user IDs that are expected to have
   *   identical cache context keys, since they have identical memberships in
   *   the defined test groups.
   *
   * @see \Drupal\Tests\og\Kernel\Cache\Context\OgRoleCacheContextTest::testMemberships()
   *
   * @covers ::getContext
   * @dataProvider membershipsProvider
   */
  public function testMembershipsNoSql(array $group_memberships, array $expected_identical_role_groups): void {
    // 'Mock' the unmockable singleton that holds the Drupal settings array by
    // instantiating it and populating it with a random salt.
    new Settings(['hash_salt' => $this->randomMachineName()]);

    // Mock the private key that will be returned by the private key handler.
    $this->privateKey->get()->willReturn($this->randomMachineName());

    // Mock the users that are defined in the test case.
    $user_ids = array_keys($group_memberships);
    $users = array_map(function ($user_id) {
      /** @var \Drupal\Core\Session\AccountInterface|\Prophecy\Prophecy\ObjectProphecy $user */
      $user = $this->prophesize(AccountInterface::class);
      $user->id()->willReturn($user_id);
      return $user->reveal();
    }, array_combine($user_ids, $user_ids));

    // Set up the memberships that are expected to be returned from the
    // membership manager.
    $memberships = [];
    // Use incremental IDs for the OgMembership object. These are not actually
    // used for calculating the cache context, but this simulates that in the
    // database no two memberships will have the same ID.
    $membership_id = 0;
    foreach ($group_memberships as $user_id => $group_entity_type_ids) {
      $memberships[$user_id] = [];
      foreach ($group_entity_type_ids as $group_entity_type_id => $group_ids) {
        foreach ($group_ids as $group_id => $roles) {
          // Construct the role IDs that will be returned by the membership.
          $roles_ids = array_map(function (string $role_name) use ($group_entity_type_id) {
            return "{$group_entity_type_id}-bundle-{$role_name}";
          }, $roles);
          // Mock the expected returns of method calls on the membership.
          /** @var \Drupal\og\OgMembershipInterface|\Prophecy\Prophecy\ObjectProphecy $membership */
          $membership = $this->prophesize(OgMembershipInterface::class);
          $membership->getGroupEntityType()->willReturn($group_entity_type_id);
          $membership->getGroupBundle()->willReturn('bundle');
          $membership->getGroupId()->willReturn($group_id);
          $membership->getRolesIds()->willReturn($roles_ids);
          $memberships[$user_id][++$membership_id] = $membership->reveal();
        }
      }
    }

    // Calculate the cache context keys for every user.
    $cache_context_ids = [];
    foreach ($users as $user_id => $user) {
      // When the memberships for every user in the test case are requested from
      // the membership manager, the respective array of memberships will be
      // returned.
      $this->membershipManager->getMemberships($user_id)->willReturn($memberships[$user_id]);
      $cache_context_ids[$user_id] = $this->getContextResult($user);
    }

    // Loop over the expected results and check that all users that have
    // identical roles have the same cache context key.
    foreach ($expected_identical_role_groups as $expected_identical_role_group) {
      // Check that the cache context keys for all users in the group are
      // identical.
      $cache_context_ids_subset = array_intersect_key($cache_context_ids, array_flip($expected_identical_role_group));
      $this->assertTrue(count(array_unique($cache_context_ids_subset)) === 1);

      // Also check that the cache context keys for the other users are
      // different than the ones from our test group.
      $cache_context_id_from_test_group = reset($cache_context_ids_subset);
      $cache_context_ids_from_other_users = array_diff_key($cache_context_ids, array_flip($expected_identical_role_group));
      $this->assertFalse(in_array($cache_context_id_from_test_group, $cache_context_ids_from_other_users));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCacheContext(AccountInterface $user = NULL): CacheContextInterface {
    return new OgRoleCacheContext($user, $this->entityTypeManager->reveal(), $this->membershipManager->reveal(), $this->database->reveal(), $this->privateKey->reveal());
  }

}
