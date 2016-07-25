<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
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
  public static $modules = ['og', 'node', 'user', 'system'];

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The group entity, of type node.
   *
   * @var Node
   */
  protected $group;

  /**
   * The user object.
   *
   * @var User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Installing needed schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');

    $this->groupBundle = $this->randomMachineName();

    $this->user = User::create(['name' => $this->randomString()]);
    $this->user->save();

    $this->group = Node::create([
      'title' => $this->randomString(),
      'uid' => $this->user->id(),
      'type' => $this->groupBundle,
    ]);
  }

  /**
   * Testing OG membership role referencing.
   */
  public function testRoleCreate() {
    // Creating a content editor role.
    $content_editor = OgRole::create();
    $content_editor
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->setName('content_editor')
      ->setLabel('Content editor')
      ->grantPermission('administer group');
    $content_editor->save();

    // Create a group member role.
    $group_member = OgRole::create();
    $group_member
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->setName('group_member')
      ->setLabel('Group member');
    $group_member->save();

    /** @var OgMembership $membership */
    $membership = OgMembership::create();
    $membership
      ->setUser($this->user)
      ->setGroup($this->group)
      // Assign only the content editor role for now.
      ->setRoles([$content_editor])
      ->save();

    $roles_ids = $membership->getRolesIds();
    $this->assertTrue(in_array($content_editor->id(), $roles_ids), 'The membership has the content editor role.');

    // Adding another role to the membership.
    $membership->addRole($group_member);
    $roles_ids = $membership->getRolesIds();

    $this->assertTrue(in_array($content_editor->id(), $roles_ids), 'The membership has the content editor role.');
    $this->assertTrue(in_array($group_member->id(), $roles_ids), 'The membership has the group member role.');

    // Remove a role.
    $membership->revokeRole($content_editor);

    $roles_ids = $membership->getRolesIds();
    $this->assertFalse(in_array($content_editor->id(), $roles_ids), 'The membership does not have the content editor role after is has been revoked.');
    $this->assertTrue(in_array($group_member->id(), $roles_ids), 'The membership has the group member role.');

    // Check if the role has permission from the membership.
    $this->assertFalse($membership->hasPermission('administer group'), 'The user has permission to administer groups.');
    $membership->addRole($content_editor);
    $this->assertTrue($membership->hasPermission('administer group'), 'The user has permission to administer groups.');
  }

}
