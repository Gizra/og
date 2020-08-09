<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Traits;

use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgRoleInterface;

/**
 * Provides functionality shared between tests for the OgRoleCacheContext.
 *
 * This cache context has a unit test as well as a kernel test.
 */
trait OgRoleCacheContextTestTrait {

  /**
   * Return the context result.
   *
   * @return string
   *   The context result.
   */
  protected function getContextResult(AccountInterface $user = NULL): string {
    return $this->getCacheContext($user)->getContext();
  }

  /**
   * Data provider for testMemberships().
   *
   * Format of the user list:
   *
   * @code
   *   $user_id => [
   *     $group_entity_type_id => [
   *       $group_id => [
   *         $role_name,
   *       ],
   *     ],
   *   ],
   * @endcode
   *
   * @return array
   *   An array of test data, each array consisting of two arrays. The first
   *   array defines a list of users, the groups of which they are a member, and
   *   the roles the users have in the groups. It is in the format described
   *   above.
   *   The second array contains arrays of user IDs that are expected to have
   *   identical cache context keys, since they have identical memberships in
   *   the defined test groups.
   *
   * @see ::testMemberships()
   */
  public function membershipsProvider(): array {
    return [
      [
        // Set up a number of users with different roles within different
        // groups.
        [
          // An anonymous user which is not a member of any groups.
          0 => [],
          // A user which is a normal member of three groups, one group of type
          // node, and two groups of type entity_test.
          1 => [
            'node' => [
              1 => [OgRoleInterface::AUTHENTICATED],
            ],
            'entity_test' => [
              1 => [OgRoleInterface::AUTHENTICATED],
              2 => [OgRoleInterface::AUTHENTICATED],
            ],
          ],
          // A user which is a member of one single group.
          2 => ['entity_test' => [2 => [OgRoleInterface::AUTHENTICATED]]],
          // A user which is an administrator in one group and a regular member
          // in another. Note that an administrator is also a normal member, so
          // the user will have two roles.
          3 => [
            'node' => [
              1 => [
                OgRoleInterface::AUTHENTICATED,
                OgRoleInterface::ADMINISTRATOR,
              ],
              2 => [OgRoleInterface::AUTHENTICATED],
            ],
          ],
          // A user which has a custom role 'moderator' in three different
          // groups.
          4 => [
            'node' => [
              1 => ['moderator', OgRoleInterface::AUTHENTICATED],
            ],
            'entity_test' => [
              1 => ['moderator', OgRoleInterface::AUTHENTICATED],
              2 => ['moderator', OgRoleInterface::AUTHENTICATED],
            ],
          ],
          // A user which has the same memberships as user 1, and one additional
          // membership.
          5 => [
            'node' => [
              1 => [OgRoleInterface::AUTHENTICATED],
              2 => [OgRoleInterface::AUTHENTICATED],
            ],
            'entity_test' => [
              1 => [OgRoleInterface::AUTHENTICATED],
              2 => [OgRoleInterface::AUTHENTICATED],
            ],
          ],
          // A user which has the same memberships as user 1, but defined in a
          // different order.
          6 => [
            'entity_test' => [
              2 => [OgRoleInterface::AUTHENTICATED],
              1 => [OgRoleInterface::AUTHENTICATED],
            ],
            'node' => [
              1 => [OgRoleInterface::AUTHENTICATED],
            ],
          ],
          // A user which has the same memberships as user 3.
          7 => [
            'node' => [
              1 => [
                OgRoleInterface::AUTHENTICATED,
                OgRoleInterface::ADMINISTRATOR,
              ],
              2 => [OgRoleInterface::AUTHENTICATED],
            ],
          ],
          // A user which has the same memberships as user 4, with the
          // memberships declared in a different order.
          8 => [
            'node' => [
              1 => [OgRoleInterface::AUTHENTICATED, 'moderator'],
            ],
            'entity_test' => [
              1 => [OgRoleInterface::AUTHENTICATED, 'moderator'],
              2 => [OgRoleInterface::AUTHENTICATED, 'moderator'],
            ],
          ],
          // A user which has the same memberships as user 4, with the
          // memberships declared in the same order.
          9 => [
            'node' => [
              1 => ['moderator', OgRoleInterface::AUTHENTICATED],
            ],
            'entity_test' => [
              1 => ['moderator', OgRoleInterface::AUTHENTICATED],
              2 => ['moderator', OgRoleInterface::AUTHENTICATED],
            ],
          ],
          // A user which has the same memberships as user 4, but with one
          // role missing.
          10 => [
            'node' => [
              1 => ['moderator', OgRoleInterface::AUTHENTICATED],
            ],
            'entity_test' => [
              1 => [OgRoleInterface::AUTHENTICATED],
              2 => ['moderator', OgRoleInterface::AUTHENTICATED],
            ],
          ],
        ],
        // Define the users which have identical memberships and should have an
        // identical hash in their cache context key.
        [
          [0],
          [1, 6],
          [2],
          [3, 7],
          [4, 8, 9],
          [5],
          [10],
        ],
      ],
    ];
  }

}
