<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\user\Entity\User;

/**
 * Tests entity reference selection plugins.
 *
 * @group og
 */
class SelectionHandlerTest extends KernelTestBase {

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
    'options',
  ];

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user1;

  /**
   * A user object.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user2;

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
    $this->groupBundle = Unicode::strtolower($this->randomMachineName());
    $this->groupContentBundle = Unicode::strtolower($this->randomMachineName());

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

    // Define the group content as group.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle);

    // Add og audience field to group content.
    $this->fieldDefinition = Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'node', $this->groupContentBundle);

    // Get the storage of the field.
    $this->selectionHandler = Og::getSelectionHandler($this->fieldDefinition, ['handler_settings' => ['field_mode' => 'default']]);

    // Create two users.
    $this->user1 = User::create(['name' => $this->randomString()]);
    $this->user1->save();

    $this->user2 = User::create(['name' => $this->randomString()]);
    $this->user2->save();
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
   * We need to verify that each user get the groups he own in the normal widget
   * and the other users group's in the other groups widget and vice versa.
   */
  public function testSelectionHandlerResults() {
    $user1_groups = $this->createGroups(2, $this->user1);
    $user2_groups = $this->createGroups(2, $this->user2);

    // Checking that the user get the groups he mange.
    $this->setCurrentAccount($this->user1);
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals($user1_groups, array_keys($groups[$this->groupBundle]));

    $this->setCurrentAccount($this->user2);
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals($user2_groups, array_keys($groups[$this->groupBundle]));

    // Check the other groups.
    $this->selectionHandler = Og::getSelectionHandler($this->fieldDefinition, ['handler_settings' => ['field_mode' => 'admin']]);

    $this->setCurrentAccount($this->user1);
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals($user2_groups, array_keys($groups[$this->groupBundle]));

    $this->setCurrentAccount($this->user2);
    $groups = $this->selectionHandler->getReferenceableEntities();
    $this->assertEquals($user1_groups, array_keys($groups[$this->groupBundle]));
  }

  /**
   * Creating groups for a given user.
   *
   * @param int $amount
   *   The number of groups to create.
   * @param \Drupal\user\Entity\User $user
   *   The user object which owns the groups.
   *
   * @return ContentEntityBase[]
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
