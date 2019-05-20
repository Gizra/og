<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\Role;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\user\Entity\User;

/**
 * Tests entity reference selection plugins.
 *
 * @group og
 */
class OgSelectionTest extends KernelTestBase {

  /**
   * The selection handler.
   *
   * @var \Drupal\og\Plugin\EntityReferenceSelection\OgSelection
   */
  protected $selectionHandler;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'entity_reference',
    'node',
    'og',
  ];

  /**
   * A site-wide group administrator.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupAdmin;

  /**
   * A group manager.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupManager;

  /**
   * A regular group member.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupMember;

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The machine name of the group content node type.
   *
   * @var string
   */
  protected $groupContentBundle;

  /**
   * The field definition used in this test.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');

    // Setting up variables.
    $this->groupBundle = mb_strtolower($this->randomMachineName());
    $this->groupContentBundle = mb_strtolower($this->randomMachineName());

    // Create a group.
    NodeType::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
    ])->save();

    // Create a group content type.
    NodeType::create([
      'type' => $this->groupContentBundle,
      'name' => $this->randomString(),
    ])->save();

    // Define bundle as group.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle);

    // Add og audience field to group content.
    $this->fieldDefinition = Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $this->groupContentBundle);

    // The selection handler for the field.
    $this->selectionHandler = Og::getSelectionHandler($this->fieldDefinition);

    // Create users.
    $this->groupAdmin = User::create(['name' => $this->randomString()]);
    $this->groupAdmin->save();

    $this->groupManager = User::create(['name' => $this->randomString()]);
    $this->groupManager->save();

    $this->groupMember = User::create(['name' => $this->randomString()]);
    $this->groupMember->save();

    // Assign administer-group permission to admin.
    $role = Role::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
    ]);

    $role
      ->grantPermission('administer group')
      ->save();

    $this
      ->groupAdmin
      ->addRole($role->id());
  }

  /**
   * Testing the OG manager selection handler.
   *
   * We need to verify that the manager selection handler will use the default
   * selection manager of the entity which the audience field referencing to.
   *
   * i.e: When the field referencing to node, we need verify we got the default
   * node selection handler.
   */
  public function testSelectionHandler() {
    $this->assertEquals(get_class($this->selectionHandler->getSelectionHandler()), 'Drupal\node\Plugin\EntityReferenceSelection\NodeSelection');
  }

  /**
   * Testing OG selection handler results.
   *
   * We need to verify that each user gets the groups they own in the normal
   * widget and the other users' groups in the other groups widget and vice
   * versa.
   */
  public function testSelectionHandlerResults() {
    $user1_groups = $this->createGroups(5, $this->groupAdmin);
    $user2_groups = $this->createGroups(5, $this->groupManager);

    $all_groups_ids = array_merge($user1_groups, $user2_groups);

    // Admin user can create content on all groups.
    $this->setCurrentAccount($this->groupAdmin);
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals($all_groups_ids, array_keys($groups[$this->groupBundle]));

    // Group manager can create content in their groups.
    $this->setCurrentAccount($this->groupManager);
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals($user2_groups, array_keys($groups[$this->groupBundle]));

    // Non-group member.
    $this->setCurrentAccount($this->groupMember);
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertTrue(empty($groups[$this->groupBundle]));

    // Group member access to create content.
    $group_id = $user1_groups[0];
    $group = Node::load($group_id);
    $membership = Og::createMembership($group, $this->groupMember);
    $membership->save();

    // Group member cannot create content in their groups when they don't have
    // access to.
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertTrue(empty($groups[$this->groupBundle]));

    // Grant OG permission.
    $og_role = OgRole::getRole('node', $this->groupBundle, OgRoleInterface::AUTHENTICATED);
    $og_role
      ->grantPermission("create {$this->groupContentBundle} content")
      ->save();

    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals([$group_id], array_keys($groups[$this->groupBundle]));
  }

  /**
   * Creating groups for a given user.
   *
   * @param int $amount
   *   The number of groups to create.
   * @param \Drupal\user\Entity\User $user
   *   The user object which owns the groups.
   *
   * @return \Drupal\Core\Entity\ContentEntityBase[]
   *   An array of group entities.
   */
  protected function createGroups($amount, User $user) {
    $groups = [];

    for ($i = 0; $i <= $amount; $i++) {
      $group = Node::create([
        'title' => $this->randomString(),
        'uid' => $user->id(),
        'type' => $this->groupBundle,
      ]);
      $group->save();

      $groups[] = $group->id();
    }

    return $groups;
  }

  /**
   * Sets the current account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to switch to.
   */
  protected function setCurrentAccount(AccountInterface $account) {
    $this->container->get('account_switcher')->switchTo($account);
  }

}
