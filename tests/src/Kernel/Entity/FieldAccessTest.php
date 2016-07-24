<?php

namespace Drupal\Tests\og\Kernel\Entity;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\Og;
use Drupal\Component\Utility\Unicode;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

/**
 * Tests OG specific field access.
 *
 * @group og
 */
class FieldAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'entity_test',
    'og',
  ];

  /**
   * The machine name of the group node type.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The regular authenticated user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $authenticatedUser;

  /**
   * The entity access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $userAccessControlHandler;

  /**
   * The audience field definition.
   *
   * @var \Drupal\field\Entity\FieldConfig
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
    $this->installEntitySchema('entity_test');
    $this->installSchema('system', 'sequences');

    // Create a group bundle type.
    $this->groupBundle = Unicode::strtolower($this->randomMachineName());

    // Create a group.
    EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
    ])->save();

    // Define the group content as group.
    Og::groupManager()->addGroup('entity_test', $this->groupBundle);

    // Add OG audience field to users.
    Og::createField(OgGroupAudienceHelper::DEFAULT_FIELD, 'user', 'user');

    Role::create(['id' => 'group_admin', 'label' => 'Group Admin'])
      ->grantPermission('administer group')
      ->grantPermission('administer users')
      ->save();

    // Create two users.
    $this->adminUser = User::create(['name' => $this->randomString()]);
    $this->adminUser->addRole('group_admin');
    $this->adminUser->save();

    $this->authenticatedUser = User::create(['name' => $this->randomString()]);
    $this->authenticatedUser->save();

    $this->fieldDefinition = $this->adminUser->getFieldDefinition(OgGroupAudienceHelper::DEFAULT_FIELD);
    $this->userAccessControlHandler = \Drupal::entityTypeManager()->getAccessControlHandler('user');
  }

  /**
   * Test anonymous users.
   *
   * @dataProvider providerTestAnonymousUserAccess
   */
  public function testAnonymousUserAccess($operation) {
    $this->assertTrue($this->userAccessControlHandler->fieldAccess($operation, $this->fieldDefinition, new AnonymousUserSession()));
  }

  /**
   * Data provider for testAnonymousUserAccess.
   *
   * @return array
   *   Array with the operation names.
   */
  public function providerTestAnonymousUserAccess() {
    return [
      ['edit'],
      ['view'],
      ['delete'],
    ];
  }

  /**
   * Test authenticated users.
   *
   * @dataProvider providerTestAuthenticatedUserAccess
   */
  public function testAuthenticatedUserAccess($operation, $admin_account, $expected) {
    $account = $admin_account ? $this->adminUser : $this->authenticatedUser;
    $this->assertEquals($expected, $this->userAccessControlHandler->fieldAccess($operation, $this->fieldDefinition, $account));
  }

  /**
   * Data provider for testAuthenticatedUserAccess.
   *
   * @return array
   *   Array with the operation, a boolean indicating if it is an admin user,
   *   and a boolean indicating the expected result.
   */
  public function providerTestAuthenticatedUserAccess() {
    return [
      ['edit', TRUE, TRUE],
      // Edit for an authenticated user without the 'administer group'
      // permission should be restricted.
      ['edit', FALSE, FALSE],
      ['view', TRUE, TRUE],
      ['view', FALSE, TRUE],
      ['delete', TRUE, TRUE],
      ['delete', FALSE, TRUE],
    ];
  }

  /**
   * Test authenticated users.
   *
   * @dataProvider providerTestAuthenticatedUserAccessWithAccessBypass
   */
  public function testAuthenticatedUserAccessWithAccessBypass($operation, $admin_account, $expected) {
    $account = $admin_account ? $this->adminUser : $this->authenticatedUser;
    // Set the access bypass setting in the storage definition.
    $this->fieldDefinition->setSetting('access_override', TRUE);
    $this->assertEquals($expected, $this->userAccessControlHandler->fieldAccess($operation, $this->fieldDefinition, $account));
  }

  /**
   * Data provider for testAuthenticatedUserAccessWithAccessBypass.
   *
   * @return array
   *   Array with the operation, a boolean indicating if it is an admin user,
   *   and a boolean indicating the expected result.
   */
  public function providerTestAuthenticatedUserAccessWithAccessBypass() {
    return [
      ['edit', TRUE, TRUE],
      // Access bypass for the field definition is enabled. So auth users should
      // now see this field.
      ['edit', FALSE, TRUE],
      ['view', TRUE, TRUE],
      ['view', FALSE, TRUE],
      ['delete', TRUE, TRUE],
      ['delete', FALSE, TRUE],
    ];
  }

}
