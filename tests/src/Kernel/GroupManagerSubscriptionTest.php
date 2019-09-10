<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Tests if the group manager is subscribed automatically upon group creation.
 *
 * @group og
 */
class GroupManagerSubscriptionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'og',
    'og_test',
    'system',
    'user',
  ];

  /**
   * Test group owner.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $owner;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManagerInterface
   */
  protected $groupTypeManager;

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->groupTypeManager = $this->container->get('og.group_type_manager');
    $this->membershipManager = $this->container->get('og.membership_manager');

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    // Create a group type.
    NodeType::create([
      'name' => $this->randomString(),
      'type' => 'group',
    ])->save();
    $this->groupTypeManager->addGroup('node', 'group');

    // Create a test group owner.
    $this->owner = User::create(['name' => $this->randomString()]);
    $this->owner->save();

    // Create a custom role that will be used to check if other modules can
    // override the membership that is created by default.
    $role = OgRole::create();
    $role
      ->setName('moderator')
      ->setLabel($this->randomString())
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->save();
  }

  /**
   * Tests whether a group manager is subscribed when creating a group.
   *
   * @dataProvider groupManagerSubscriptionProvider
   */
  public function testGroupManagerSubscription($group_has_owner, $membership_is_overridden) {
    // Create the group, with a specific title that can be detected by the hook
    // implementation that overrides the creation of the membership.
    // @see og_test_entity_insert()
    $group = Node::create([
      'title' => $membership_is_overridden ? 'membership is overridden' : 'membership is not overridden',
      'type' => 'group',
    ]);
    // Set the group owner if the test requires it.
    if ($group_has_owner) {
      $group->setOwner($this->owner);
    }
    $group->save();

    // Check that a membership has only been created if the group had an owner
    // set.
    $membership = $this->membershipManager->getMembership($group, $this->owner->id());
    $this->assertEquals($group_has_owner, !empty($membership));

    // Check if the membership has been overridden.
    $this->assertEquals($membership_is_overridden, $this->isMembershipOverridden($membership));
  }

  /**
   * Checks if the membership is overridden by a custom hook implementation.
   *
   * @param \Drupal\og\OgMembershipInterface $membership
   *   The OG Membership to check. If empty the membership does not exist.
   *
   * @return bool
   *   Whether or not the membership is overridden.
   */
  protected function isMembershipOverridden(OgMembershipInterface $membership = NULL) {
    // If the membership doesn't exist it is not overridden.
    if (empty($membership)) {
      return FALSE;
    }

    // If the membership is overridden, it will have the 'moderator' role
    // assigned to it.
    // @see og_test_entity_insert()
    return $membership->hasRole('node-group-moderator');
  }

  /**
   * Data provider for ::testGroupManagerSubscription().
   *
   * @return array
   *   An array of test data arrays, each test data array having the following
   *   three values:
   *   - A boolean indicating whether or not the group manager will be marked as
   *     the author of the group when the entity is created.
   *   - A boolean indicating whether or not another hook_entity_insert() will
   *     fire first and will override the membership.
   */
  public static function groupManagerSubscriptionProvider() {
    return [
      // Test a group created by the group manager.
      [
        // Whether or not to set the group manager as author of the group
        // entity.
        TRUE,
        // Whether or not another hook_entity_insert() implementation will fire
        // first and create a subscription.
        FALSE,
      ],
      // Test a group created by an anonymous user.
      [
        FALSE,
        FALSE,
      ],
      // Test a group created by the group manager, but the subscription will be
      // already be created by another hook_entity_insert() implementation. This
      // allows us to test whether developers can override the automatic
      // creation of the membership.
      [
        TRUE,
        TRUE,
      ],
    ];
  }

}
