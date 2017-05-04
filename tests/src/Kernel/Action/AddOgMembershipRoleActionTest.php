<?php

namespace Drupal\Tests\og\Kernel\Action;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\simpletest\UserCreationTrait;

/**
 * Tests the AddOgMembershipRole action.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Action\AddOgMembershipRole
 */
class AddOgMembershipRoleActionTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'entity_reference',
    'node',
    'og',
  ];

  /**
   * The ID of the plugin under test.
   *
   * @var string
   */
  protected $plugin_id = 'og_membership_add_role_action';

  /**
   * An array of test users.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $users;

  /**
   * An array of test memberships.
   *
   * @var \Drupal\og\OgMembershipInterface[]
   */
  protected $memberships;

  /**
   * A test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

  /**
   * An array of test OG roles.
   *
   * @var \Drupal\og\OgRoleInterface[]
   */
  protected $roles;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

//    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
//$this->installSchema('node', 'node_access');
    $this->installSchema('system', ['queue', 'sequences']);

    $this->membershipManager = $this->container->get('og.membership_manager');
    $this->groupTypeManager = $this->container->get('og.group_type_manager');

    // Create a group entity type.
    $group_bundle = Unicode::strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $group_bundle,
      'name' => $this->randomString(),
    ])->save();
    $this->groupTypeManager->addGroup('node', $group_bundle);

    // Create a test group.
    $this->group = Node::create([
      'title' => $this->randomString(),
      'type' => $group_bundle,
    ]);
    $this->group->save();

    // Store a reference to the administrator role for our group type.
    $this->roles['administrator'] = OgRole::getRole('node', $group_bundle, OgRoleInterface::ADMINISTRATOR);

    // Create a 'moderator' role that is allowed to manage group members.
    $this->roles['moderator'] = OgRole::create();
    $this->roles['moderator']
      ->setGroupType('node')
      ->setGroupBundle($group_bundle)
      ->setName('moderator')
      ->grantPermission('manage members')
      ->save();

    // Create test users.
    $this->createUsers();
  }

  /**
   * Checks if the action can be performed correctly.
   *
   * @param string $membership
   *   The membership on which to perform the action.
   *
   * @covers ::execute
   * @dataProvider executeProvider
   */
  public function testExecute($membership, $default_role_name, $expected_role_name) {
    /** @var \Drupal\og\Plugin\Action\AddOgMembershipRole $plugin */
    $configuration = !empty($default_role_name) ? ['role_name' => $default_role_name] : [];
    $plugin = $this->getPlugin($configuration);
    $plugin->execute($this->memberships[$membership]);

    $has_role = (bool) array_filter($this->memberships[$membership]->getRoles(), function (OgRole $role) use ($expected_role_name) {
      return $role->getName() === $expected_role_name;
    });
    $this->assertTrue($has_role);
  }

  /**
   * Checks that the user can perform the action on the membership.
   *
   * @param string $user
   *   The user for which to perform the access check.
   * @param string $membership
   *   The membership on which to perform the access check.
   *
   * @covers ::access
   * @dataProvider accessProvider
   */
  public function testAccess($user, $membership) {
    $plugin = $this->getPlugin();
    $access_as_object = $plugin->access($this->memberships[$membership], $this->users[$user], TRUE);
    $this->assertTrue($access_as_object instanceof AccessResultAllowed);

    $access_as_boolean = $plugin->access($this->memberships[$membership], $this->users[$user], FALSE);
    $this->assertTrue($access_as_boolean);
  }

  /**
   * Checks that the user cannot perform the action on the membership.
   *
   * @covers ::access
   * @dataProvider noAccessProvider
   */
  public function testNoAccess($user, $membership) {
    $plugin = $this->getPlugin();
    $access_as_object = $plugin->access($this->memberships[$membership], $this->users[$user], TRUE);
    $this->assertFalse($access_as_object instanceof AccessResultAllowed);

    $access_as_boolean = $plugin->access($this->memberships[$membership], $this->users[$user], FALSE);
    $this->assertFalse($access_as_boolean);
  }

  /**
   * Data provider for testExecute().
   */
  public function executeProvider() {
    // It should be possible to add roles to any membership, regardless if they
    // are pending or blocked, or have any other membership.
    return [
      // If no default role is passed, the plugin should default to the first
      // available role (administrator).
      ['member', NULL, 'administrator'],
      ['member', 'administrator', 'administrator'],
      ['member', 'moderator', 'moderator'],
      ['pending', NULL, 'administrator'],
      ['pending', 'administrator', 'administrator'],
      ['pending', 'moderator', 'moderator'],
      ['blocked', NULL, 'administrator'],
      ['blocked', 'administrator', 'administrator'],
      ['blocked', 'moderator', 'moderator'],
      ['group_administrator', NULL, 'administrator'],
      // If an administrator is given the administrator role a second time, the
      // role should be kept intact.
      ['group_administrator', 'administrator', 'administrator'],
      ['group_administrator', 'moderator', 'moderator'],
      // If an administrator is also made a moderator, they should still keep
      // the administrator role.
      ['group_administrator', 'moderator', 'administrator'],
      ['group_moderator', NULL, 'administrator'],
      ['group_moderator', 'administrator', 'administrator'],
      // If a moderator is given the moderator role a second time, the role
      // should be kept intact.
      ['group_moderator', 'moderator', 'moderator'],
      // If a moderator is also made an administrator, they should still keep
      // the moderator role.
      ['group_moderator', 'administrator', 'moderator'],
    ];
  }

  /**
   * Data provider for testAccess().
   */
  public function accessProvider() {
    return [
      // The super user has access to this action for all member types.
      ['uid1', 'member'],
      ['uid1', 'pending'],
      ['uid1', 'blocked'],
      ['uid1', 'group_administrator'],
      ['uid1', 'group_moderator'],
      // A global administrator has access to this action for all member types.
      ['administrator', 'member'],
      ['administrator', 'pending'],
      ['administrator', 'blocked'],
      ['administrator', 'group_administrator'],
      ['administrator', 'group_moderator'],
      // A group administrator has access to this action for all member types.
      ['group_administrator', 'member'],
      ['group_administrator', 'pending'],
      ['group_administrator', 'blocked'],
      ['group_administrator', 'group_administrator'],
      ['group_administrator', 'group_moderator'],
      // A group moderator has access to this action for all member types.
      ['group_administrator', 'member'],
      ['group_administrator', 'pending'],
      ['group_administrator', 'blocked'],
      ['group_administrator', 'group_administrator'],
      ['group_administrator', 'group_moderator'],
    ];
  }

  /**
   * Data provider for testNoAccess().
   */
  public function noAccessProvider() {
    return [
      // An anonymous user doesn't have access to this action.
      ['anonymous', 'member'],
      ['anonymous', 'pending'],
      ['anonymous', 'blocked'],
      ['anonymous', 'group_administrator'],
      ['anonymous', 'group_moderator'],
      // A normal authenticated user doesn't have access.
      ['authenticated', 'member'],
      ['authenticated', 'pending'],
      ['authenticated', 'blocked'],
      ['authenticated', 'group_administrator'],
      ['authenticated', 'group_moderator'],
      // A normal group member doesn't have access.
      ['member', 'member'],
      ['member', 'pending'],
      ['member', 'blocked'],
      ['member', 'group_administrator'],
      ['member', 'group_moderator'],
      // A pending group member doesn't have access.
      ['pending', 'member'],
      ['pending', 'pending'],
      ['pending', 'blocked'],
      ['pending', 'group_administrator'],
      ['pending', 'group_moderator'],
      // A blocked group member doesn't have access.
      ['blocked', 'member'],
      ['blocked', 'pending'],
      ['blocked', 'blocked'],
      ['blocked', 'group_administrator'],
      ['blocked', 'group_moderator'],
    ];
  }

  /**
   * Creates test users.
   */
  protected function createUsers() {
    // An anonymous user.
    $this->users['anonymous'] = new AnonymousUserSession();

    // The first user created (with UID 1) is the super user.
    $this->users['uid1'] = $this->createUser();

    // A normal authenticated user.
    $this->users['authenticated'] = $this->createUser();

    // An administrator with the right to administer groups globally.
    $this->users['administrator'] = $this->createUser(['administer group']);

    // A normal member of the test group.
    $this->users['member'] = $this->createUser();
    $this->memberships['member'] = $this->membershipManager->createMembership($this->group, $this->users['member']);
    $this->memberships['member']->save();

    // A pending member of the test group.
    $this->users['pending'] = $this->createUser();
    $this->memberships['pending'] = $this->membershipManager->createMembership($this->group, $this->users['pending'], OgMembershipInterface::STATE_PENDING);
    $this->memberships['pending']->save();

    // A blocked member of the test group.
    $this->users['blocked'] = $this->createUser();
    $this->memberships['blocked'] = $this->membershipManager->createMembership($this->group, $this->users['blocked'], OgMembershipInterface::STATE_BLOCKED);
    $this->memberships['blocked']->save();

    // A group administrator. This is a special case since this role is
    // considered to have all permissions.
    $this->users['group_administrator'] = $this->createUser();
    $this->memberships['group_administrator'] = $this->membershipManager->createMembership($this->group, $this->users['group_administrator'])->addRole($this->roles['administrator']);
    $this->memberships['group_administrator']->save();

    // A group moderator that has the right to administer group members.
    $this->users['group_moderator'] = $this->createUser();
    $this->memberships['group_moderator'] = $this->membershipManager->createMembership($this->group, $this->users['group_moderator'])->addRole($this->roles['moderator']);
    $this->memberships['group_moderator']->save();
  }

  /**
   * Returns an instance of the plugin under test.
   *
   * @var array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return \Drupal\Core\Action\ActionInterface
   */
  public function getPlugin($configuration = []) {
    /** @var \Drupal\Core\Action\ActionManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.action');
    return $plugin_manager->createInstance($this->plugin_id, $configuration);
  }

}
