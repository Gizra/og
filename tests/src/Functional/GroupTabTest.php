<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Functional;

use Drupal\Core\Url;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\Tests\og\Traits\OgMembershipCreationTrait;

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
  public static $modules = ['node', 'og', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

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
   * The group membership for another user.
   *
   * @var \Drupal\og\OgMembershipInterface
   */
  protected $anotherMembership;

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

    // Create node author user.
    $this->authorUser = $this->createUser();

    // Saving the group node creates a membership of the author.
    $this->group = Node::create([
      'type' => $this->bundle1,
      'title' => $this->randomString(),
      'uid' => $this->authorUser->id(),
    ]);
    $this->group->save();
    $another_user = $this->createUser();
    $this->anotherMembership = $this->createOgMembership($this->group, $another_user);

    $this->nonGroup = Node::create([
      'type' => $this->bundle2,
      'title' => $this->randomString(),
      'uid' => $this->authorUser->id(),
    ]);
    $this->nonGroup->save();

    $this->user1 = $this->drupalCreateUser(['administer organic groups']);
    $this->user2 = $this->drupalCreateUser();
  }

  /**
   * Tests access to the group tab and pages.
   */
  public function testGroupTabAccess() {
    foreach ($this->groupTabScenarios() as $scenario) {
      [$account, $code] = $scenario;
      $this->drupalLogin($account);

      $entity_type_id = $this->group->getEntityTypeId();
      // @see \Drupal\og\Routing\RouteSubscriber::alterRoutes() for the
      // routes.
      $route_name = "entity.$entity_type_id.og_admin_routes";
      $route_parameters = [$entity_type_id => $this->group->id()];
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
        'group' => $this->group->id(),
        'entity_type_id' => $entity_type_id,
        'og_membership_type' => OgMembershipInterface::TYPE_DEFAULT,
      ];
      $this->drupalGet(Url::fromRoute('entity.og_membership.add_form', $add_form_parameters));
      $this->assertSession()->statusCodeEquals($code);

      $this->drupalGet($this->anotherMembership->toUrl());
      $this->assertSession()->statusCodeEquals($code);

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
      [$this->user1, 200],
      [$this->user2, 403],
    ];
  }

}
