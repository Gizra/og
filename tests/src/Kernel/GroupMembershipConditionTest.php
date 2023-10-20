<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\user\Entity\User;

/**
 * Tests the GroupMembership condition plugin.
 *
 * @group og
 */
class GroupMembershipConditionTest extends KernelTestBase {

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
   * The condition plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * OG group node.
   *
   * @var \Drupal\node\Entity\node
   */
  protected $group;

  /**
   * OG role.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $role;

  /**
   * Member user.
   *
   * @var \Drupal\user\entity\User
   */
  protected $member;

  /**
   * Non-member user.
   *
   * @var \Drupal\user\entity\User
   */
  protected $nonMember;

  /**
   * Og Membership.
   *
   * @var \Drupal\og\Entity\OgMembership
   */
  protected $membership;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->conditionManager = $this->container->get('plugin.manager.condition');
    $this->groupTypeManager = $this->container->get('og.group_type_manager');

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('og_membership');
    $this->installSchema('system', ['sequences']);

    // Create a node group content type.
    NodeType::create([
      'name' => $this->randomString(),
      'type' => 'node_group',
    ])->save();
    $this->groupTypeManager->addGroup('node', 'node_group');

    // Create a group node.
    $this->group = Node::create([
      'title' => $this->randomString(),
      'type' => 'node_group',
    ]);
    $this->group->save();

    // Create a custom role for the group.
    $role_name = $this->randomMachineName();
    $this->role = OgRole::create();
    $this->role
      ->setName($role_name)
      ->setLabel($role_name)
      ->setGroupType('node')
      ->setGroupBundle('node_group')
      ->save();

    // Create a member user.
    $this->member = User::create([
      'name' => $this->randomString(),
    ]);
    $this->member->save();

    // Add the member user to the group with
    // the custom role.
    $this->membership = Og::createMembership($this->group, $this->member);
    $this->membership->setRoles([$this->role]);
    $this->membership->save();

    // Create a non-member user.
    $this->nonMember = User::create([
      'name' => $this->randomString(),
    ]);
    $this->nonMember->save();
  }

  /**
   * Test the member has access.
   */
  public function testMembership() {
    $plugin_instance = $this->conditionManager->createInstance('og_group_membership')
      ->setConfig('og_membership', TRUE)
      ->setConfig('negate', FALSE)
      ->setContextValue('og', $this->group)
      ->setContextValue('user', $this->member);

    $this->assertEquals(TRUE, $plugin_instance->execute());
  }

  /**
   * Test the member has access via role.
   */
  public function testMembershipRole() {
    $plugin_instance = $this->conditionManager->createInstance('og_group_membership')
      ->setConfig('og_membership', TRUE)
      ->setConfig('og_roles', [$this->role->id() => $this->role->getLabel()])
      ->setConfig('negate', FALSE)
      ->setContextValue('og', $this->group)
      ->setContextValue('user', $this->member);

    $this->assertEquals(TRUE, $plugin_instance->execute());
  }

  /**
   * Test the non-member does not have access.
   */
  public function testNonMembership() {
    $plugin_instance = $this->conditionManager->createInstance('og_group_membership')
      ->setConfig('og_membership', TRUE)
      ->setConfig('negate', FALSE)
      ->setContextValue('og', $this->group)
      ->setContextValue('user', $this->nonMember);

    $this->assertEquals(FALSE, $plugin_instance->execute());
  }

}
