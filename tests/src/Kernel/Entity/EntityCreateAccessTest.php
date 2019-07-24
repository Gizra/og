<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests access to the create entity form through the user interface.
 *
 * @see og_entity_create_access().
 *
 * @group og
 */
class EntityCreateAccessTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

  /**
   * The group type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  public $groupType;

  /**
   * The group content type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  public $groupContentType;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    // Create a "group" node type and turn it into a group type.
    $this->groupType = NodeType::create([
      'type' => 'group',
      'name' => $this->randomString(),
    ]);
    $this->groupType->save();
    Og::groupTypeManager()->addGroup('node', 'group');

    // Add a group audience field to the "post" node type, turning it into a
    // group content type.
    $this->groupContentType = NodeType::create([
      'type' => 'post',
      'name' => $this->randomString(),
    ]);
    $this->groupContentType->save();
    Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', 'post');
  }

  /**
   * Tests that users that can only view cannot access the entity creation form.
   */
  public function testViewPermissionDoesNotGrantCreateAccess() {
    // Create test user.
    $user = User::create(['name' => $this->randomString()]);
    $user->save();

    // Create a group.
    Node::create([
      'title' => $this->randomString(),
      'type' => 'group',
      'uid' => $user->id(),
    ])->save();

    // Make sure the anonymous user exists. This normally is created in the
    // install hook of the User module, but this doesn't run in a KernelTest.
    // @see user_install()
    \Drupal::entityTypeManager()
      ->getStorage('user')
      ->create(['uid' => 0, 'status' => 0, 'name' => ''])
      ->save();

    // Grant the anonymous user permission to view published content.
    /** @var \Drupal\user\Entity\Role $role */
    $role = Role::create(['id' => Role::ANONYMOUS_ID, 'label' => 'anonymous user'])
      ->grantPermission('access content');
    $role->save();

    // Verify that the user does not have access to the entity create form of
    // the group content type.
    /** @var \Drupal\node\Access\NodeAddAccessCheck $node_access_check */
    $node_access_check = $this->container->get('access_check.node.add');
    $result = $node_access_check->access(User::getAnonymousUser(), $this->groupContentType);
    $this->assertNotInstanceOf('\Drupal\Core\Access\AccessResultAllowed', $result);

    // Test that the user can access the entity create form when the permission
    // to create group content is granted. Note that node access control is
    // cached, so we need to reset it when we change permissions.
    $this->container->get('entity_type.manager')->getAccessControlHandler('node')->resetCache();
    $role->grantPermission('create post content')->trustData()->save();
    $result = $node_access_check->access(User::getAnonymousUser(), $this->groupContentType);
    $this->assertInstanceOf('\Drupal\Core\Access\AccessResultAllowed', $result);
  }

}
