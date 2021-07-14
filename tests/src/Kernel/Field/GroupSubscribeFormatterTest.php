<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Field;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;

/**
 * Tests the OG group formatter.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter
 */
class GroupSubscribeFormatterTest extends EntityKernelTestBase {

  /**
   * The owner of the group.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

  /**
   * The bundle ID of the test group.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $mymodules = ['node', 'field', 'og', 'options'];
    // This $modules property goes from public to private in Drupal 8 to 9.
    static::$modules = array_merge(static::$modules, $mymodules);
    parent::setUp();
    $this->installEntitySchema('og_membership');
    // Create bundle.
    $this->groupBundle = mb_strtolower($this->randomMachineName());

    // Create a node type.
    $node_type = NodeType::create([
      'type' => $this->groupBundle,
      'name' => $this->groupBundle,
    ]);
    $node_type->save();

    // Define the bundles as groups.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle);

    // Create node author user.
    $this->user = $this->createUser();

    $this->group = Node::create([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $this->user->id(),
    ]);
    $this->group->save();
  }

  /**
   * Tests the formatter for a group owner.
   */
  public function testGroupOwner() {
    $this->drupalSetCurrentUser($this->user);
    $elements = $this->getElements();
    $this->assertEquals('You are the group manager', $elements[0]['#value']);
  }

  /**
   * Tests the formatter for an "anonymous" group member.
   */
  public function testGroupNonMember() {
    $user1 = $this->createUser();
    $this->drupalSetCurrentUser($user1);

    $elements = $this->getElements();
    $this->assertEquals('Request group membership', $elements[0]['#title']);

    $role = OgRole::getRole('node', $this->groupBundle, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe without approval')
      ->save();
    $elements = $this->getElements();
    $this->assertEquals('Subscribe to group', $elements[0]['#title']);
  }

  /**
   * Tests the formatter for no subscribe permission.
   */
  public function testNoSubscribePermission() {
    $user1 = $this->createUser();
    $this->drupalSetCurrentUser($user1);
    $role = OgRole::getRole('node', $this->groupBundle, OgRoleInterface::ANONYMOUS);
    $role
      ->revokePermission('subscribe')
      ->save();

    $elements = $this->getElements();
    $this->assertStringStartsWith('This is a closed group.', $elements[0]['#value']->render());
  }

  /**
   * Tests the formatter for an active, pending, or blocked member.
   */
  public function testMember() {
    $user1 = $this->createUser();
    $this->drupalSetCurrentUser($user1);

    $elements = $this->getElements();
    $this->assertEquals('Request group membership', $elements[0]['#title']);

    /** @var \Drupal\og\MembershipManager $membership_manager */
    $membership_manager = $this->container->get('og.membership_manager');
    $membership = $membership_manager->createMembership($this->group, $user1, OgMembershipInterface::STATE_ACTIVE);
    $membership->save();
    $elements = $this->getElements();
    $this->assertEquals('Unsubscribe from group', $elements[0]['#title']);

    $membership->setState(OgMembershipInterface::STATE_PENDING);
    $membership->save();
    $elements = $this->getElements();
    $this->assertEquals('Unsubscribe from group', $elements[0]['#title']);

    $membership->setState(OgMembershipInterface::STATE_BLOCKED);
    $membership->save();
    $elements = $this->getElements();
    $this->assertTrue(empty($elements[0]));
  }

  /**
   * Helper method; Return the renderable elements from the formatter.
   *
   * @return array
   *   The renderable array.
   */
  protected function getElements() {
    return $this->group->get('og_group')->view();
  }

}
