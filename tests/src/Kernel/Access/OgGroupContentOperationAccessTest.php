<?php

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\Component\Utility\Unicode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;

/**
 * Test access to group content operations for group members.
 *
 * @group og
 */
class OgGroupContentOperationAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

  /**
   * An array of test users.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users;

  /**
   * A test group.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group;

  /**
   * The bundle ID of the test group.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * An array of test roles.
   *
   * @var \Drupal\og\Entity\OgRole[]
   *   Note that we're not using OgRoleInterface because of a class inheritance
   *   limitation in PHP 5.
   */
  protected $roles;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    $this->groupBundle = Unicode::strtolower($this->randomMachineName());

    // Create a test user with UID 1. This user has universal access.
    $this->users['uid1'] = User::create(['name' => $this->randomString()]);
    $this->users['uid1']->save();

    // Create a user that will serve as the group owner. There are no special
    // permissions granted to the group owner, so this user is not tested.
    $group_owner = User::create(['name' => $this->randomString()]);
    $group_owner->save();

    // Declare that the test entity is a group type.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

    // Create the test group.
    $this->group = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $group_owner->id(),
    ]);
    $this->group->save();

    // @todo Create test group content types for the 'newsletter' and 'article'.

    // Create 3 test roles with associated permissions. We will simulate a
    // project that has two group content types:
    // - 'newsletter_subscription': Any registered user can create entities of
    //   this type, even if they are not a member of the group.
    // - 'article': These can only be created by group members. Normal members
    //   can edit and delete their own articles, while admins can edit and
    //   delete any article.
    $permission_matrix = [
      OgRoleInterface::ANONYMOUS => [
        'create newsletter_subscription entity_test',
        'update own newsletter_subscription entity_test',
        'delete own newsletter_subscription entity_test',
      ],
      OgRoleInterface::AUTHENTICATED => [
        'create newsletter_subscription entity_test',
        'update own newsletter_subscription entity_test',
        'delete own newsletter_subscription entity_test',
        'create article content',
        'edit own article content',
        'delete own article content',
      ],
      // The administrator is not explicitly granted permission to edit or
      // delete their own group content. Having the 'any' permission should be
      // sufficient to also be able to edit their own content.
      OgRoleInterface::ADMINISTRATOR => [
        'create newsletter_subscription entity_test',
        'update any newsletter_subscription entity_test',
        'delete any newsletter_subscription entity_test',
        'create article content',
        'edit any article content',
        'delete any article content',
      ],
    ];

    foreach ($permission_matrix as $role_name => $permissions) {
      $role_id = "{$this->group->getEntityTypeId()}-{$this->group->bundle()}-$role_name";
      $this->roles[$role_name] = OgRole::load($role_id);
      foreach ($permissions as $permission) {
        $this->roles[$role_name]->grantPermission($permission);
      }
      $this->roles[$role_name]->save();

      // Create a test user with this role.
      $this->users[$role_name] = User::create(['name' => $this->randomString()]);
      $this->users[$role_name]->save();

      // Subscribe the user to the group.
      // Skip creation of the membership for the non-member user. It is actually
      // fine to save this membership, but in the most common use case this
      // membership will not exist in the database.
      if ($role_name !== OgRoleInterface::ANONYMOUS) {
        /** @var OgMembership $membership */
        $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
        $membership
          ->setUser($this->users[$role_name]->id())
          ->setEntityId($this->group->id())
          ->setGroupEntityType($this->group->getEntityTypeId())
          ->addRole($this->roles[$role_name]->id())
          ->setState(OgMembershipInterface::STATE_ACTIVE)
          ->save();
      }
    }
  }

  /**
   * Test access to group content entity operations.
   *
   * @dataProvider accessProvider
   */
  public function testAccess($group_content_entity_type_id, $group_content_bundle_id, $expected_access) {
    $og_access = $this->container->get('og.access');
  }

  /**
   * Data provider for ::testAccess().
   *
   * @return array
   *   And array of test data sets. Each set consisting of:
   *   - A string representing the group content entity type ID upon which the
   *     operation is performed. Can be either 'node' or 'entity_test'.
   *   - A string representing the group content bundle ID upon which the
   *     operation is performed. Can be either 'newsletter_subscription' or
   *     'article'.
   *   - An array mapping the different users and the possible operations, and
   *     whether or not the user is expected to be granted access to this
   *     operation, depending on whether they own the content or not.
   */
  public function accessProvider() {
    return [
      [
        'entity_test',
        'newsletter_subscription',
        [
          // The super user and the administrator have the right to create,
          // update and delete any newsletter subscription.
          'uid1' => [
            'create' => TRUE,
            'update' => ['own' => TRUE, 'any' => TRUE],
            'delete' => ['own' => TRUE, 'any' => TRUE],
          ],
          OgRoleInterface::ADMINISTRATOR => [
            'create' => TRUE,
            'update' => ['own' => TRUE, 'any' => TRUE],
            'delete' => ['own' => TRUE, 'any' => TRUE],
          ],
          // Non-members and members have the right to subscribe to the
          // newsletter, and to manage or delete their own newsletter
          // subscriptions.
          OgRoleInterface::ANONYMOUS => [
            'create' => TRUE,
            'update' => ['own' => TRUE, 'any' => FALSE],
            'delete' => ['own' => TRUE, 'any' => FALSE],
          ],
          OgRoleInterface::AUTHENTICATED => [
            'create' => TRUE,
            'update' => ['own' => TRUE, 'any' => FALSE],
            'delete' => ['own' => TRUE, 'any' => FALSE],
          ],
        ],
      ],
      [
        'node',
        'article',
        [
          // The super user and the administrator have the right to create,
          // update and delete any article.
          'uid1' => [
            'create' => TRUE,
            'update' => ['own' => TRUE, 'any' => TRUE],
            'delete' => ['own' => TRUE, 'any' => TRUE],
          ],
          OgRoleInterface::ADMINISTRATOR => [
            'create' => TRUE,
            'update' => ['own' => TRUE, 'any' => TRUE],
            'delete' => ['own' => TRUE, 'any' => TRUE],
          ],
          // Non-members do not have the right to create or manage any article.
          OgRoleInterface::ANONYMOUS => [
            'create' => FALSE,
            'update' => ['own' => FALSE, 'any' => FALSE],
            'delete' => ['own' => FALSE, 'any' => FALSE],
          ],
          // Members have the right to create new articles, and to manage their
          // own articles.
          OgRoleInterface::AUTHENTICATED => [
            'create' => TRUE,
            'update' => ['own' => TRUE, 'any' => FALSE],
            'delete' => ['own' => TRUE, 'any' => FALSE],
          ],
        ],
      ],
    ];
  }

}
