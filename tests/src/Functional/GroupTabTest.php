<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the "Group" tab.
 *
 * @group og
 */
class GroupTabTest extends BrowserTestBase {

  use OgMembershipCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'og', 'views', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $groupNode;

  /**
   * Test non-group entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nonGroup;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $bundle1;

  /**
   * The group author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * The group administrator user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdminUser;

  /**
   * A group user with 'manage members' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupManagerUser;

  /**
   * The node group membership for another user.
   *
   * @var \Drupal\og\OgMembershipInterface
   */
  protected $anotherNodeMembership;

  /**
   * A group that is of type entity_test.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $groupTestEntity;

  /**
   * The entity_test group membership for another user.
   *
   * @var \Drupal\og\OgMembershipInterface
   */
  protected $anotherTestEntityMembership;

  /**
   * A administrative user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * A non-author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create bundles.
    $this->bundle1 = mb_strtolower($this->randomMachineName());
    $this->bundle2 = mb_strtolower($this->randomMachineName());

    // Create node types.
    $node_type1 = NodeType::create([
      'type' => $this->bundle1,
      'name' => $this->bundle1,
    ]);
    $node_type1->save();

    $node_type2 = NodeType::create([
      'type' => $this->bundle2,
      'name' => $this->bundle2,
    ]);
    $node_type2->save();

    // Define the first bundle as group.
    Og::groupTypeManager()->addGroup('node', $this->bundle1);
    // Define the entity_test entity as a group.
    Og::groupTypeManager()->addGroup('entity_test', 'entity_test');
    // Add a role with manager members permission for each group type.
    $values = [
      'name' => 'manager',
      'label' => 'Member manager',
      'permissions' => ['manage members'],
    ];
    $node_role = OgRole::create($values);
    $node_role->setGroupType('node');
    $node_role->setGroupBundle($this->bundle1);
    $node_role->save();
    $this->assertSame(['manage members'], $node_role->getPermissions());
    $entity_test_role = OgRole::create($values);
    $entity_test_role->setGroupType('entity_test');
    $entity_test_role->setGroupBundle('entity_test');
    $entity_test_role->save();

    // Create node author user. The "z" prevents matching user 1 who gets
    // the name "admin" in the test.
    $this->authorUser = $this->createUser([], 'author-adminz');
    $this->groupManagerUser = $this->createUser([], 'group-members-manager');

    // Saving the group node creates a membership for the author.
    $this->groupNode = Node::create([
      'type' => $this->bundle1,
      'title' => $this->randomString(),
      'uid' => $this->authorUser->id(),
    ]);
    $this->groupNode->save();
    $this->groupAdminUser = $this->createUser([], 'adminz-group');
    $this->createOgMembership($this->groupNode, $this->groupAdminUser, [OgRoleInterface::ADMINISTRATOR]);
    $this->createOgMembership($this->groupNode, $this->groupManagerUser, [$node_role->getName()]);
    $another_user = $this->createUser([], 'another');
    $this->anotherNodeMembership = $this->createOgMembership($this->groupNode, $another_user);

    $this->groupTestEntity = EntityTest::create([
      'name' => $this->randomString(),
      'user_id' => $this->authorUser->id(),
    ]);
    $this->groupTestEntity->save();
    $this->createOgMembership($this->groupTestEntity, $this->groupAdminUser, [OgRoleInterface::ADMINISTRATOR]);
    $this->createOgMembership($this->groupTestEntity, $this->groupManagerUser, [$entity_test_role->getName()]);
    $this->anotherTestEntityMembership = $this->createOgMembership($this->groupTestEntity, $another_user);

    $this->nonGroup = Node::create([
      'type' => $this->bundle2,
      'title' => $this->randomString(),
      'uid' => $this->authorUser->id(),
    ]);
    $this->nonGroup->save();

    $this->user1 = $this->drupalCreateUser(['administer organic groups'], 'adminz-all-groups');
    $this->user2 = $this->drupalCreateUser([], 'somebody');
  }

  /**
   * Tests access to the group tab and pages.
   */
  public function testMembershipAdd() {
    $loop = 0;
    $random_name = $this->randomMachineName();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manger */
    $entity_type_manger = $this->container->get('entity_type.manager');
    /** @var \Drupal\og\MembershipManager $membership_manager */
    $membership_manager = $this->container->get('og.membership_manager');
    foreach ($this->membershipAddScenarios() as $scenario) {
      [$account] = $scenario;
      $this->drupalLogin($account);
      $group_data = [
        [$this->groupNode, $this->anotherNodeMembership],
        [$this->groupTestEntity, $this->anotherTestEntityMembership],
      ];

      foreach ($group_data as $data) {
        [$group, $membership] = $data;
        /** @var \Drupal\og\OgMembershipInterface $membership */
        $exiting_member = $membership->getOwner();
        $this->drupalGet($this->groupMemberAddFormUrl($group));
        $value = $exiting_member->getDisplayName() . ' (' . $exiting_member->id() . ')';
        $this->submitForm(['Username' => $value], 'Save');
        $this->assertSession()->pageTextMatches('/The user .+ is already a member in this group/');
        $match = 'adminz';
        // Test entity query match.
        $found = $entity_type_manger->getStorage('user')
          ->getQuery()
          ->accessCheck()
          ->condition('uid', 0, '<>')
          ->condition('name', $match, 'CONTAINS')
          ->execute();
        $this->assertCount(3, $found, print_r($found, TRUE));
        // Two of the three possible matches are already members.
        $this->assertAutoCompleteMatches($group, $match, 1);
        // Verify that we can add a new user after matching.
        $new_user = $this->createUser([], $random_name . $loop++);
        $json_data = $this->assertAutoCompleteMatches($group, $new_user->getDisplayName(), 1);
        $this->drupalGet($this->groupMemberAddFormUrl($group));
        $this->submitForm(['Username' => $json_data[0]['value']], 'Save');
        $this->assertSession()->pageTextMatches('/Added .+ to .+/');
        $this->assertTrue($membership_manager->isMember($group, $new_user->id()));
        $new_membership = $membership_manager->getMembership($group, $new_user->id());
        $this->drupalGet($new_membership->toUrl('edit-form'));
        $this->submitForm(['state' => OgMembershipInterface::STATE_BLOCKED], 'Save');
        $new_membership = $entity_type_manger
          ->getStorage('og_membership')
          ->loadUnchanged($new_membership->id());
        $this->assertSame(OgMembershipInterface::STATE_BLOCKED, $new_membership->getState());
        $this->drupalGet($new_membership->toUrl('delete-form'));
        $this->submitForm([], 'Delete');
        $new_membership = $entity_type_manger
          ->getStorage('og_membership')
          ->loadUnchanged($new_membership->id());
        $this->assertNull($new_membership);
      }
    }
  }

  /**
   * Provide data for testMembershipAdd.
   *
   * @return array[]
   *   Array of test scenarios.
   */
  protected function membershipAddScenarios(): array {
    return [
      [$this->authorUser],
      [$this->groupAdminUser],
      [$this->user1],
    ];
  }

  /**
   * Test adding a group-blocked user and a site-wide blocked user.
   */
  public function testBlockedUserAdd() {
    $this->drupalLogin($this->groupAdminUser);
    $blocked_user = $this->drupalCreateUser([], 'bbblocked', FALSE, ['status' => 0]);
    /** @var \Drupal\og\MembershipManager $membership_manager */
    $membership_manager = $this->container->get('og.membership_manager');
    $group_data = [
      [$this->groupNode, $this->anotherNodeMembership],
      [$this->groupTestEntity, $this->anotherTestEntityMembership],
    ];

    foreach ($group_data as $data) {
      [$group, $membership] = $data;
      /** @var \Drupal\og\OgMembershipInterface $membership */
      $exiting_member = $membership->getOwner();
      $membership->setState(OgMembershipInterface::STATE_BLOCKED);
      $membership->save();
      // Directly test autocomplete endpoint.
      $this->assertAutoCompleteMatches($group, $exiting_member->getDisplayName(), 0);
      $this->drupalGet($this->groupMemberAddFormUrl($group));
      $value = $exiting_member->getDisplayName() . ' (' . $exiting_member->id() . ')';
      $this->submitForm(['Username' => $value], 'Save');
      $this->assertSession()->pageTextMatches('/The user .+ is already a member in this group/');
      $this->drupalGet($this->groupMemberAddFormUrl($group));
      // API validate too.
      $new_membership = $membership_manager->createMembership($group, $exiting_member);
      $errors = $new_membership->validate();
      $this->assertTrue(count($errors) > 0);
      // Directly test autocomplete endpoint.
      $this->assertAutoCompleteMatches($group, 'bbbl', 0);
      $this->drupalGet($this->groupMemberAddFormUrl($group));
      $value = $blocked_user->getDisplayName() . ' (' . $blocked_user->id() . ')';
      $this->submitForm(['Username' => $value], 'Save');
      $this->assertSession()->pageTextMatches('/This entity .+ cannot be referenced/');
      $this->assertFalse($membership_manager->isMember($group, $blocked_user->id(), []));
      // API validate too.
      $new_membership = $membership_manager->createMembership($group, $blocked_user);
      $errors = $new_membership->validate();
      $this->assertTrue(count($errors) > 0);
    }

    // A user with 'administer users' permission can add blocked users as group
    // members.
    $perms = ['administer users', 'administer organic groups'];
    $admin_user = $this->drupalCreateUser($perms, 'super');
    $this->drupalLogin($admin_user);
    foreach ($group_data as $data) {
      $group = $data[0];
      // Blocked user is now found in autocomplete.
      $json_data = $this->assertAutoCompleteMatches($group, 'bbbl', 1);
      $this->drupalGet($this->groupMemberAddFormUrl($group));
      $this->submitForm(['Username' => $json_data[0]['value']], 'Save');
      $this->assertTrue($membership_manager->isMember($group, $blocked_user->id()));
    }
  }

  /**
   * Get the Url for the member add for for a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group.
   *
   * @return \Drupal\Core\Url
   *   The Url object.
   */
  protected function groupMemberAddFormUrl(EntityInterface $group): Url {
    $entity_type_id = $group->getEntityTypeId();
    $add_form_parameters = [
      'group' => $group->id(),
      'entity_type_id' => $entity_type_id,
      'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
    ];
    return Url::fromRoute('entity.og_membership.add_form', $add_form_parameters);
  }

  /**
   * Assert an expected number of matches looking to add a user to a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group.
   * @param string $match
   *   The search string.
   * @param int $expected_count
   *   The expected count of matches.
   *
   * @return array
   *   The data decoded from JSON.
   */
  protected function assertAutoCompleteMatches(EntityInterface $group, string $match, int $expected_count): array {
    $this->drupalGet($this->groupMemberAddFormUrl($group));
    $page = $this->getSession()->getPage();
    $input = $page->findField('edit-uid-0-target-id');
    $path = $input->getAttribute('data-autocomplete-path');
    // Remove potential base path when the site is under a subdirectory.
    $base_path = rtrim(base_path(), '/');
    if ($base_path && strpos($path, $base_path) === 0) {
      $path = substr($path, strlen($base_path));
    }
    $this->drupalGet($path, ['query' => ['q' => $match]]);
    $header = $this->getSession()->getResponseHeader('content-type');
    $this->assertSame('application/json', $header);
    $out = $this->getSession()->getPage()->getContent();
    $data = json_decode($out, TRUE);
    $this->assertCount($expected_count, $data, $out);
    return $data;
  }

  /**
   * Tests access to the group tab and pages.
   */
  public function testGroupTabAccess() {
    foreach ($this->groupTabScenarios() as $scenario) {
      [$account, $code] = $scenario;
      if (!$account->isAnonymous()) {
        $this->drupalLogin($account);
      }
      $group_data = [
        [$this->groupNode, $this->anotherNodeMembership],
        [$this->groupTestEntity, $this->anotherTestEntityMembership],
      ];

      foreach ($group_data as $data) {
        [$group, $membership] = $data;

        $entity_type_id = $group->getEntityTypeId();
        // @see \Drupal\og\Routing\RouteSubscriber::alterRoutes() for the
        // routes.
        $route_name = "entity.$entity_type_id.og_admin_routes";
        $route_parameters = [$entity_type_id => $group->id()];
        // For nodes the base admin path is
        // 'group/node/' . $this->group->id() . '/admin'.
        $this->drupalGet(Url::fromRoute($route_name, $route_parameters));
        $this->assertSession()->statusCodeEquals($code);

        // This page is rendered by a view. For nodes the path is
        // 'group/node/' . $this->group->id() . '/admin/members'.
        $members_list_route_name = $route_name . '.members';
        $this->drupalGet(Url::fromRoute($members_list_route_name, $route_parameters));
        $this->assertSession()->statusCodeEquals($code);

        $add_member_route_name = $route_name . '.add_membership_page';
        $this->drupalGet(Url::fromRoute($add_member_route_name, $route_parameters));
        $this->assertSession()->statusCodeEquals($code);

        $add_form_parameters = [
          'group' => $group->id(),
          'entity_type_id' => $entity_type_id,
          'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
        ];
        $this->drupalGet(Url::fromRoute('entity.og_membership.add_form', $add_form_parameters));
        $this->assertSession()->statusCodeEquals($code);

        $this->drupalGet($membership->toUrl());
        $this->assertSession()->statusCodeEquals($code);
      }

      $entity_type_id = $this->nonGroup->getEntityTypeId();
      $route_name = "entity.$entity_type_id.og_admin_routes";
      $route_parameters = [$entity_type_id => $this->nonGroup->id()];
      $this->drupalGet(Url::fromRoute($route_name, $route_parameters));
      $this->assertSession()->statusCodeEquals(403);
    }
  }

  /**
   * Provide data for testGroupTab().
   *
   * @return array[]
   *   Array of test scenarios.
   */
  protected function groupTabScenarios(): array {
    return [
      [$this->authorUser, 200],
      [$this->groupAdminUser, 200],
      [$this->groupManagerUser, 200],
      [$this->user1, 200],
      [$this->user2, 403],
      [User::load(0), 403],
    ];
  }

}
