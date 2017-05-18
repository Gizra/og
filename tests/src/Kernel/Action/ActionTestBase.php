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
 * Base class for testing action plugins.
 */
abstract class ActionTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The ID of the plugin under test.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og', 'system', 'user'];

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

    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
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
    $this->memberships['pending'] = $this->membershipManager->createMembership($this->group, $this->users['pending'])->setState(OgMembershipInterface::STATE_PENDING);
    $this->memberships['pending']->save();

    // A blocked member of the test group.
    $this->users['blocked'] = $this->createUser();
    $this->memberships['blocked'] = $this->membershipManager->createMembership($this->group, $this->users['blocked'])->setState(OgMembershipInterface::STATE_BLOCKED);
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
   *   The plugin instance.
   */
  public function getPlugin($configuration = []) {
    /** @var \Drupal\Core\Action\ActionManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.action');
    return $plugin_manager->createInstance($this->pluginId, $configuration);
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
  abstract public function testExecute($membership);

  /**
   * Checks that the user can perform the action on the membership.
   *
   * @covers ::access
   */
  public function testAccess() {
    // Dramatically improve testing speed by looping over all test cases in a
    // single test, so that the expensive setup is not executed over and over.
    $test_cases = $this->accessProvider();
    foreach ($test_cases as $test_case) {
      list($user, $membership) = $test_case;

      $plugin = $this->getPlugin();
      $access_as_object = $plugin->access($this->memberships[$membership], $this->users[$user], TRUE);
      $this->assertTrue($access_as_object instanceof AccessResultAllowed);

      $access_as_boolean = $plugin->access($this->memberships[$membership], $this->users[$user], FALSE);
      $this->assertTrue($access_as_boolean);
    }
  }

  /**
   * Checks that the user cannot perform the action on the membership.
   *
   * @covers ::access
   */
  public function testNoAccess() {
    // Dramatically improve testing speed by looping over all test cases in a
    // single test, so that the expensive setup is not executed over and over.
    $test_cases = $this->noAccessProvider();
    foreach ($test_cases as $test_case) {
      list($user, $membership) = $test_case;
      $plugin = $this->getPlugin();
      $access_as_object = $plugin->access($this->memberships[$membership], $this->users[$user], TRUE);
      $this->assertFalse($access_as_object instanceof AccessResultAllowed);

      $access_as_boolean = $plugin->access($this->memberships[$membership], $this->users[$user], FALSE);
      $this->assertFalse($access_as_boolean);
    }
  }

  /**
   * Data provider for testExecute().
   */
  abstract public function executeProvider();

  /**
   * Data provider for testAccess().
   */
  abstract public function accessProvider();

  /**
   * Data provider for testNoAccess().
   */
  abstract public function noAccessProvider();

}