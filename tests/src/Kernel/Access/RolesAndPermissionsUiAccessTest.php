<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;

/**
 * Tests access to the routes that serve the roles and permissions UI.
 *
 * This is handled in a kernel test rather than a functional test because there
 * is a large matrix of users and routes to test and this would be very slow to
 * handle in a functional test.
 *
 * @group og
 */
class RolesAndPermissionsUiAccessTest extends KernelTestBase {

  use OgMembershipCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block_content',
    'og',
    'og_ui',
    'options',
    'system',
    'user',
  ];

  /**
   * A test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

  /**
   * Test users.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $users;

  /**
   * A test custom role.
   *
   * @var \Drupal\og\OgRoleInterface
   */
  protected $role;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('block_content');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    // Create a "group" bundle on the Custom Block entity type and turn it into
    // a group. Note we're not using the Entity Test entity for this since it
    // does not have real support for multiple bundles.
    BlockContentType::create(['id' => 'group'])->save();
    Og::groupTypeManager()->addGroup('block_content', 'group');

    // Create a custom 'moderator' role for our group type.
    $this->role = OgRole::create();
    $this->role
      ->setGroupType('block_content')
      ->setGroupBundle('group')
      ->setName('moderator')
      ->save();

    // Create an anonymous test user.
    $this->users['anonymous'] = User::getAnonymousUser();

    // Create the root user. Since this is the first user we create this user
    // will get UID 1 which is reserved for the root user.
    $this->users['root user'] = $this->createUser();
    $this->users['root user']->save();

    // Create another global administrator. This is a user with a role which has
    // the 'isAdmin' flag, indicating that this user has all possible
    // permissions.
    $this->users['global administrator'] = $this->createUser([], NULL, TRUE);
    $this->users['global administrator']->save();

    // Create a 'normal' authenticated user which is not part of the test group.
    $this->users['non-member'] = $this->createUser();
    $this->users['non-member']->save();

    // Create a user which has the global permission to administer organic
    // groups.
    $this->users['global group administrator'] = $this->createUser(['administer organic groups']);
    $this->users['global group administrator']->save();

    // Create a test user for each membership type.
    $membership_types = [
      // The group administrator.
      'administrator' => [
        'state' => OgMembershipInterface::STATE_ACTIVE,
        'role_name' => OgRoleInterface::ADMINISTRATOR,
      ],
      // A regular member of the group.
      'member' => [
        'state' => OgMembershipInterface::STATE_ACTIVE,
        'role_name' => OgRoleInterface::AUTHENTICATED,
      ],
      // A blocked user.
      'blocked' => [
        'state' => OgMembershipInterface::STATE_BLOCKED,
        'role_name' => OgRoleInterface::AUTHENTICATED,
      ],
      // A pending user.
      'pending' => [
        'state' => OgMembershipInterface::STATE_PENDING,
        'role_name' => OgRoleInterface::AUTHENTICATED,
      ],
      // A "moderator" (a custom role).
      'moderator' => [
        'state' => OgMembershipInterface::STATE_ACTIVE,
        'role_name' => OgRoleInterface::AUTHENTICATED,
      ],
    ];
    foreach ($membership_types as $user_key => $membership_info) {
      $user = $this->createUser();
      $this->users[$user_key] = $user;

      // The administrator is the first user to be created. In this case also
      // create the group and set the administrator as the owner. The membership
      // will be created automatically.
      switch ($user_key) {
        case 'administrator':
          $this->group = BlockContent::create([
            'title' => $this->randomString(),
            'type' => 'group',
            'uid' => $user->id(),
          ]);
          $this->group->save();
          break;

        // Create a normal membership for the other users.
        default:
          $this->createOgMembership($this->group, $user, [$membership_info['role_name']], $membership_info['state']);
          break;
      }
    }
  }

  /**
   * Checks whether users have access to routes.
   *
   * @param array $routes
   *   An array of routes to test. Each value is an array with two keys:
   *   - A string containing the route machine name to check.
   *   - An array of route parameters to set.
   * @param array $access_matrix
   *   An associative array, keyed by user key, with as value a boolean that
   *   represents whether or not the user is expected to have access to the
   *   route.
   *
   * @dataProvider routeAccessDataProvider
   */
  public function testRouteAccess(array $routes, array $access_matrix): void {
    foreach ($routes as $route_info) {
      [$route, $parameters] = $route_info;
      foreach ($access_matrix as $user_key => $should_have_access) {
        $has_access = $this->container->get('access_manager')->checkNamedRoute($route, $parameters, $this->users[$user_key], FALSE);
        $message = "The '$user_key' user is " . ($should_have_access ? '' : 'not ') . "expected to have access to the '$route' route.";
        $this->assertEquals($should_have_access, $has_access, $message);
      }
    }
  }

  /**
   * Data provider for ::testRouteAccess().
   *
   * @return array
   *   An array of test cases. Each test case is an indexed array with the
   *   following values:
   *   - An array of routes to test. Each value is an array with two keys:
   *     - A string containing the route machine name to check.
   *     - An array of route parameters for the route.
   *   - An associative array keyed by users, with the value a boolean
   *     representing whether or not the user is expected to have access to the
   *     routes.
   */
  public function routeAccessDataProvider() {
    return [
      [
        [
          // The main page of the Organic Groups configuration.
          [
            'og_ui.admin_index',
            [],
          ],
          // The settings form.
          [
            'og_ui.settings',
            [],
          ],
          // The roles overview table for all group types.
          [
            'og_ui.roles_permissions_overview',
            ['type' => 'roles'],
          ],
          // The permissions overview table for all group types.
          [
            'og_ui.roles_permissions_overview',
            ['type' => 'permissions'],
          ],
          // The permissions table for all roles of a specific group type.
          [
            'og_ui.permissions_overview',
            [
              'entity_type_id' => 'block_content',
              'bundle_id' => 'group',
            ],
          ],
          // The permissions form for administrators of a single group type.
          [
            'og_ui.permissions_edit_form',
            [
              'entity_type_id' => 'block_content',
              'bundle_id' => 'group',
              'role_name' => OgRoleInterface::ADMINISTRATOR,
            ],
          ],
          // The permissions form for non-members of a single group type.
          [
            'og_ui.permissions_edit_form',
            [
              'entity_type_id' => 'block_content',
              'bundle_id' => 'group',
              'role_name' => OgRoleInterface::ANONYMOUS,
            ],
          ],
          // The permissions form for members of a single group type.
          [
            'og_ui.permissions_edit_form',
            [
              'entity_type_id' => 'block_content',
              'bundle_id' => 'group',
              'role_name' => OgRoleInterface::AUTHENTICATED,
            ],
          ],
          // The permissions form for "moderators" (a custom role) of a single
          // group type.
          [
            'og_ui.permissions_edit_form',
            [
              'entity_type_id' => 'block_content',
              'bundle_id' => 'group',
              'role_name' => 'moderator',
            ],
          ],
          // The overview of available roles for a group type.
          [
            'entity.og_role.collection',
            [
              'entity_type_id' => 'block_content',
              'bundle_id' => 'group',
            ],
          ],
          // The form to add a new role to a group type.
          [
            'entity.og_role.add_form',
            [
              'entity_type_id' => 'block_content',
              'bundle_id' => 'group',
            ],
          ],
          // The form to edit a custom role belonging to a group type.
          [
            'entity.og_role.edit_form',
            [
              'og_role' => 'block_content-group-moderator',
            ],
          ],
          // The form to delete a custom role belonging to a group type.
          [
            'entity.og_role.delete_form',
            [
              'og_role' => 'block_content-group-moderator',
            ],
          ],
        ],
        [
          // Since these routes are for managing the roles and permissions of
          // all groups of the tested entity type and bundle, the forms should
          // only be accessible to the root user, global administrators that
          // have all permissions, and users that have the permission
          // 'administer organic groups'.
          // Group administrators should not have access to these
          // pages, but they will have access to the forms that deal with
          // group-specific roles and permissions. These are not tested here.
          'root user' => TRUE,
          'global administrator' => TRUE,
          'global group administrator' => TRUE,
          'anonymous' => FALSE,
          'non-member' => FALSE,
          'administrator' => FALSE,
          'member' => FALSE,
          'blocked' => FALSE,
          'pending' => FALSE,
        ],
      ],
    ];
  }

}
