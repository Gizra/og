<?php

/**
 * @file
 * Contains Drupal\Tests\og\Kernel\Entity\OgMembershipRoleReference.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
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
   * @var string
   *
   * The machine name of the group node type.
   */
  protected $groupBundle;

  /**
   * @var Node
   */
  protected $group;

  /**
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

    $this->groupBundle = Unicode::strtolower($this->randomMachineName());

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
    $og_role = OgRole::create();
    $og_role
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->setGroupType('node')
      ->setGroupBundle('group')
      ->grantPermission('administer group')
      ->save();

    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setEntityType('node')
      ->setEntityId($this->group->id())
      ->setUser($this->user)
      ->setRoles([$og_role->id()])
      ->save();
  }

}
