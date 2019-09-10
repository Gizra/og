<?php

namespace Drupal\Tests\og\Kernel\Form;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access to the create entity form through the user interface.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Form\GroupSubscribeForm
 */
class GroupSubscribeFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'node',
    'og',
  ];

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user1;

  /**
   * A group entity.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group1;

  /**
   * A group entity.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group2;

  /**
   * A group entity.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $group3;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');

    // Create 3 test bundles and declare them as groups.
    $bundle_names = [];
    for ($i = 0; $i < 3; $i++) {
      $bundle_name = mb_strtolower($this->randomMachineName());
      NodeType::create(['type' => $bundle_name])->save();
      Og::groupTypeManager()->addGroup('node', $bundle_name);
      $bundle_names[] = $bundle_name;
    }

    // Create node author user.
    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    // Create groups.
    $this->group1 = Node::create([
      'type' => $bundle_names[0],
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group1->save();

    $this->group2 = Node::create([
      'type' => $bundle_names[1],
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group2->save();

    // Create an unpublished node, so users won't have access to it.
    $this->group3 = Node::create([
      'type' => $bundle_names[2],
      'title' => $this->randomString(),
      'uid' => $user->id(),
      'status' => NODE_NOT_PUBLISHED,
    ]);
    $this->group3->save();

    // Change the permissions of group to "subscribe".
    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::getRole('node', $bundle_names[0], OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe')
      ->save();

    // Change the permissions of group to allow "subscribe without approval".
    $role = OgRole::getRole('node', $bundle_names[1], OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe without approval')
      ->save();

    // Change the permissions of group to allow "subscribe without approval" on
    // the unpublished node.
    $role = OgRole::getRole('node', $bundle_names[2], OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe without approval')
      ->save();
  }

  /**
   * Tests subscribe confirmation related text.
   *
   * @covers ::isStateActive
   */
  public function testIsStateActive() {
    $user = $this->createUser(['access content']);

    /** @var \Drupal\og\Form\GroupSubscribeForm $form */
    $form = \Drupal::entityTypeManager()->getFormObject('og_membership', 'subscribe');

    // Pending membership.
    $membership_pending = OgMembership::create();
    $membership_pending
      ->setGroup($this->group1)
      ->setOwner($user);

    $form->setEntity($membership_pending);
    $this->assertFalse($form->isStateActive());

    // Active membership.
    $membership_active = OgMembership::create();
    $membership_active
      ->setGroup($this->group2)
      ->setOwner($user);

    $form->setEntity($membership_active);
    $this->assertTrue($form->isStateActive());

    // Confirm user has access to the group node.
    $this->assertTrue($this->group2->access('view', $user));

    // Active membership to a group without access, should result with pending
    // membership by default.
    $membership = OgMembership::create();
    $membership
      ->setGroup($this->group3)
      ->setOwner($user);

    // Confirm user doesn't have access to the unpublished group node.
    $this->assertFalse($this->group3->access('view', $user));

    // Even though the state is active, it should result with pending, as user
    // doesn't have access to the group.
    $form->setEntity($membership);
    $this->assertFalse($form->isStateActive());

    // Change the default settings, and assert state remains active.
    $this->config('og.settings')->set('deny_subscribe_without_approval', FALSE)->save();
    $this->assertTrue($form->isStateActive());
  }

  /**
   * Creates a user.
   *
   * @param array $permissions
   *   (optional) Array of permission names to assign to user.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser(array $permissions = []) {
    $values = [];
    if ($permissions) {
      // Create a new role and apply permissions to it.
      $role = Role::create([
        'id' => strtolower($this->randomMachineName(8)),
        'label' => $this->randomMachineName(8),
      ]);
      $role->save();
      user_role_grant_permissions($role->id(), $permissions);
      $values['roles'][] = $role->id();
    }

    $account = User::create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

}
