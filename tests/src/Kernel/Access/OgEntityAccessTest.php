<?php

namespace Drupal\Tests\og\Kernel\Access;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
/**
 * Test permission inside a group.
 *
 * @group og
 */
class OgEntityAccessTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = ['og', 'node', 'user', 'system', 'field'];
  /**
   * @var string
   *
   * The machine name of the group node type.
   */
  protected $groupBundle;
  /**
   * @var Node
   */
  protected $nodeGroup;
  /**
   * @var string
   */
  protected $nodeGroupBundle;
  /**
   * @var Node[]
   *
   * Contain two node group content.
   */
  protected $nodeGroupContent = [];
  /**
   * @var string
   */
  protected $entityTestBundle;
  /**
   * @var EntityTest
   */
  protected $entityTestGroup;
  /**
   * @var OgRole
   */
  protected $ogRole;
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
    $this->installConfig(['og', 'user']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');

    // Creating a user so we won't test with uid 1.
    User::create(['name' => $this->randomString()])->save();

    // Creating a user and attach a group audience field.
    $this->user = User::create(['id' => 2, 'name' => $this->randomString()]);
    $this->user->save();
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', 'user');

    /** @var Role $role */
    $role = Role::load(Role::AUTHENTICATED_ID);
    $role->grantPermission('access content');
    $role->save();

    // Creating groups.
    $this->groupBundle = $this->randomMachineName();
    NodeType::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
    ])->save();
    $this->nodeGroup = Node::create([
      'title' => $this->randomString(),
      'uid' => $this->user->id(),
      'type' => $this->groupBundle,
    ]);
    $this->nodeGroup->save();

    Og::groupManager()->addGroup('node', $this->groupBundle);


    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($this->user->id())
      ->setEntityId($this->nodeGroup->id())
      ->setGroupEntityType($this->nodeGroup->getEntityTypeId())
      ->save();

    $this->ogRole = OgRole::create();
    $this->ogRole
      ->setId('content_editor')
      ->setLabel('Content editor')
      ->setGroupType('node')
      ->setGroupBundle($this->groupBundle)
      ->save();

    // Create two node group content.
    $this->nodeGroupBundle = $this->randomMachineName();
    NodeType::create([
      'type' => $this->nodeGroupBundle,
      'name' => $this->randomString(),
    ])->save();

    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'node', $this->nodeGroupBundle);
    $this->nodeGroupContent[] = Node::create([
      'title' => $this->randomString(),
      'uid' => $this->user->id(),
      'type' => $this->nodeGroupBundle,
    ]);

    $this->nodeGroupContent[0]->set(OgGroupAudienceHelper::DEFAULT_FIELD, [$this->nodeGroup->id()]);
    $this->nodeGroupContent[0]->save();
    $this->nodeGroupContent[] = Node::create([
      'title' => $this->randomString(),
      'uid' => 3,
      'type' => $this->nodeGroupBundle,
    ]);

    $this->nodeGroupContent[1]->set(OgGroupAudienceHelper::DEFAULT_FIELD, $this->nodeGroup->id());
    $this->nodeGroupContent[1]->save();
  }
  /**
   * Testing group's permission in a node group.
   */
  public function testNodeGroupPermission() {
    $this->assertFalse((bool) $this->nodeGroupContent[0]->access('edit', $this->user));
    $this->ogRole->grantPermission('edit own ' . $this->nodeGroupContent[1]->bundle() . ' content')->save();
    $this->assertTrue($this->nodeGroupContent[0]->access('edit', $this->user));
  }

}