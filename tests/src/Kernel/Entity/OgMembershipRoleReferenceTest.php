<?php

declare(strict_types = 1);

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgAccess;
use Drupal\user\Entity\User;

/**
 * Test OG membership referencing to OG role creation.
 *
 * @group og
 */
class OgMembershipRoleReferenceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'og',
    'options',
    'system',
    'user',
  ];

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The group entity, of type node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group;

  /**
   * The user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Installing needed schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');

    // Create a "group" node type and turn it into a group type.
    $this->groupBundle = mb_strtolower($this->randomMachineName());
    NodeType::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
    ])->save();

    Og::groupTypeManager()->addGroup('node', $this->groupBundle);

    $this->user = User::create(['name' => $this->randomString()]);
    $this->user->save();

    $this->group = Node::create([
      'title' => $this->randomString(),
      'uid' => $this->user->id(),
      'type' => $this->groupBundle,
    ]);
    $this->group->save();
  }

  /**
   * Testing OG membership role referencing.
   */
  public function testRoleCreate() {
    // Creating a content editor role.
    $content_editor = OgRole::create();
    $content_editor
      ->setGroupType('node')
      ->setGroupBundle($this->groupBundle)
      ->setName('content_editor')
      ->setLabel('Content editor')
      ->grantPermission(OgAccess::ADMINISTER_GROUP_PERMISSION);
    $content_editor->save();

    // Create a group member role.
    $group_member = OgRole::create();
    $group_member
      ->setGroupType('node')
      ->setGroupBundle($this->groupBundle)
      ->setName('group_member')
      ->setLabel('Group member');
    $group_member->save();

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = Og::getMembership($this->group, $this->user);
    $membership
      // Assign only the content editor role for now.
      ->setRoles([$content_editor])
      ->save();
    $this->assertTrue($membership->hasRole($content_editor->id()), 'The membership has the content editor role.');

    // Adding another role to the membership.
    $membership->addRole($group_member);
    $this->assertTrue($membership->hasRole($content_editor->id()), 'The membership has the content editor role.');
    $this->assertTrue($membership->hasRole($group_member->id()), 'The membership has the group member role.');

    // Remove a role.
    $membership->revokeRole($content_editor);
    $this->assertFalse($membership->hasRole($content_editor->id()), 'The membership does not have the content editor role after is has been revoked.');
    $this->assertTrue($membership->hasRole($group_member->id()), 'The membership has the group member role.');

    // Check if the role has permission from the membership.
    $this->assertFalse($membership->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION), 'The user has permission to administer groups.');
    $membership->addRole($content_editor);
    $this->assertTrue($membership->hasPermission(OgAccess::ADMINISTER_GROUP_PERMISSION), 'The user has permission to administer groups.');

    // Remove a role by ID.
    $membership->revokeRoleById($group_member->id());
    $roles_ids = $membership->getRolesIds();
    $this->assertFalse(in_array($group_member->id(), $roles_ids), 'The group member role has been revoked.');
  }

}
