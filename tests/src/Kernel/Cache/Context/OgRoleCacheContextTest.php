<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Cache\Context;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\og\Traits\OgRoleCacheContextTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Cache\Context\OgRoleCacheContext;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\user\Entity\User;

/**
 * Tests the OG role cache context.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Cache\Context\OgRoleCacheContext
 */
class OgRoleCacheContextTest extends KernelTestBase {

  use OgRoleCacheContextTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'node',
    'og',
    'system',
    'user',
    'field',
  ];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG group type manager service.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The private key handler.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    $this->database = $this->container->get('database');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->groupTypeManager = $this->container->get('og.group_type_manager');
    $this->membershipManager = $this->container->get('og.membership_manager');
    $this->privateKey = $this->container->get('private_key');
  }

  /**
   * Tests generating of a cache context key for a user with no memberships.
   *
   * This is a common case, e.g. for anonymous users.
   *
   * @covers ::getContext
   */
  public function testNoMemberships(): void {
    $user = User::getAnonymousUser();

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
   * This tests the main implementation for SQL databases. The fallback
   * implementation for NoSQL databases is tested in a unit test.
   *
   * @param array $group_memberships
   *   An array that defines the roles test users have in test groups. See the
   *   data provider for a description of the format of the array.
   * @param array $expected_identical_role_groups
   *   An array containing arrays of user IDs that are expected to have
   *   identical cache context keys, since they have identical memberships in
   *   the defined test groups.
   *
   * @see \Drupal\Tests\og\Unit\Cache\Context\OgRoleCacheContextTest::testMembershipsNoSql()
   *
   * @covers ::getContext
   * @dataProvider membershipsProvider
   */
  public function testMemberships(array $group_memberships, array $expected_identical_role_groups): void {
    // Create a node group type.
    NodeType::create([
      'name' => $this->randomString(),
      'type' => 'group',
    ])->save();
    $this->groupTypeManager->addGroup('node', 'group');

    // The Entity Test entity doesn't have 'real' bundles, so we don't need to
    // create one, we can just add the group to the fake bundle.
    $this->groupTypeManager->addGroup('entity_test', 'group');

    // Create the 'moderator' role for both group types. This is used in the
    // test as a custom role in addition to the default roles 'member',
    // 'administrator', etc.
    foreach (['entity_test', 'node'] as $entity_type_id) {
      /** @var \Drupal\og\OgRoleInterface $role */
      $role = OgRole::create();
      $role
        ->setGroupType($entity_type_id)
        ->setGroupBundle('group')
        ->setName('moderator')
        ->save();
    }

    // Create the users and memberships as required by the test.
    $users = [];
    $groups = [];

    foreach ($group_memberships as $user_id => $group_entity_type_ids) {
      $users[$user_id] = $this->createUser();
      foreach ($group_entity_type_ids as $group_entity_type_id => $group_ids) {
        foreach ($group_ids as $group_id => $roles) {
          // Create the group.
          if (empty($groups[$group_entity_type_id][$group_id])) {
            $groups[$group_entity_type_id][$group_id] = $this->createGroup($group_entity_type_id);
          }
          $membership = OgMembership::create()
            ->setOwner($users[$user_id])
            ->setGroup($groups[$group_entity_type_id][$group_id]);
          foreach ($roles as $role_name) {
            $membership->addRole(OgRole::getRole($group_entity_type_id, 'group', $role_name));
          }
          $membership->save();
        }
      }
    }

    // Calculate the cache context keys for every user.
    $cache_context_ids = [];
    foreach ($users as $user_id => $user) {
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
   * Returns the instantiated cache context service which is being tested.
   *
   * @return \Drupal\Core\Cache\Context\CacheContextInterface
   *   The instantiated cache context service.
   */
  protected function getCacheContext(AccountInterface $user = NULL): CacheContextInterface {
    return new OgRoleCacheContext($user, $this->entityTypeManager, $this->membershipManager, $this->database, $this->privateKey);
  }

  /**
   * Return a group entity with the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type of the entity to create. Can be 'entity_test' or 'node'.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  protected function createGroup(string $entity_type_id): ContentEntityInterface {
    switch ($entity_type_id) {
      case 'node':
        $group = Node::create([
          'title' => $this->randomString(),
          'type' => 'group',
        ]);
        $group->save();
        break;

      default:
        $group = EntityTest::create([
          'name' => $this->randomString(),
          'type' => 'group',
        ]);
        $group->save();
    }

    return $group;
  }

}
