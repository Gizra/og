<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Kernel\Entity\FieldAccessTest.
 */

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\Component\Utility\Unicode;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests OG specific field access.
 *
 * @group og
 */
class FieldAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'field', 'entity_reference', 'node', 'og'];

  /**
   * @var string
   *
   * The machine name of the group node type.
   */
  protected $groupBundle;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $authenticatedUser;

  /**
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $userAccessControlHandler;

  /**
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

    // Create a group.
    NodeType::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
    ])->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('node', $this->groupBundle);

    // Add og audience field to users.
    Og::createField(OG_AUDIENCE_FIELD, 'user', 'user');

    Role::create(['id' => 'group_admin', 'label' => 'Group Admin'])->grantPermission('administer group')->save();

    // Create two users.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->addRole('group_admin');
    $this->adminUser->save();

    $this->authenticatedUser = User::create(['name' => $this->randomString()]);
    $this->authenticatedUser->save();

    $this->fieldDefinition = $this->adminUser->getFieldDefinition(OG_AUDIENCE_FIELD);
    $this->userAccessControlHandler = \Drupal::entityManager()->getAccessControlHandler('user');
  }

  /**
   * Test anonymous users.
   */
  public function testAnonymousUserAccess() {
    $this->assertTrue($this->userAccessControlHandler->fieldAccess('edit', $this->fieldDefinition, new AnonymousUserSession()));
  }

}
