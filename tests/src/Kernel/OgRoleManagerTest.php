<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;

/**
 * Kernel tests for the OG role manager service.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\OgRoleManager
 */
class OgRoleManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'node',
    'og',
    'system',
    'user',
  ];

  /**
   * A test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

  /**
   * A test OG role name.
   *
   * @var string
   */
  protected $roleName;

  /**
   * The OG role manager.
   *
   * @var \Drupal\og\OgRoleManagerInterface
   */
  protected $ogRoleManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add membership and config schema.
    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->roleName = mb_strtolower($this->randomMachineName());

    $this->ogRoleManager = $this->container->get('og.role_manager');

    // Create two group entity types.
    NodeType::create([
      'type' => 'node_group_type',
      'name' => $this->randomString(),
    ])->save();
    Og::groupTypeManager()->addGroup('node', 'node_group_type');

    // Bundles are implied for entity_test and don't need to be created.
    Og::groupTypeManager()->addGroup('entity_test', 'entity_test_group_type');

    // Create a custom role to verify that the tests covers custom roles as
    // well.
    $og_role = OgRole::create();
    $og_role
      ->setName($this->roleName)
      ->setLabel($this->randomString())
      ->setGroupType('node')
      ->setGroupBundle('node_group_type')
      ->grantPermission('access content')
      ->grantPermission('administer group')
      ->grantPermission('view own unpublished content')
      ->save();
  }

  /**
   * Tests that all roles are loaded for a certain group.
   *
   * @covers ::getRolesByBundle
   */
  public function testGetRolesByBundle() {
    $expected_role_ids = [
      'node-node_group_type-administrator',
      'node-node_group_type-member',
      'node-node_group_type-non-member',
      'node-node_group_type-' . $this->roleName,
    ];

    $roles = $this->ogRoleManager->getRolesByBundle('node', 'node_group_type');
    $role_ids = array_keys($roles);
    sort($expected_role_ids);
    sort($role_ids);
    $this->assertEquals($expected_role_ids, $role_ids);
  }

  /**
   * Tests searching roles by a given permission list.
   *
   * @covers ::getRolesByPermissions
   */
  public function testLoadRoleByPermissions() {
    OgRole::create()
      ->setName(mb_strtolower($this->randomMachineName()))
      ->setLabel($this->randomString())
      ->setGroupType('node')
      ->setGroupBundle('node_group_type')
      ->grantPermission('access content')
      ->grantPermission('view own unpublished content')
      ->save();

    $og_role3 = OgRole::create();
    $og_role3->setName(mb_strtolower($this->randomMachineName()))
      ->setLabel($this->randomString())
      ->setGroupType('entity_test')
      ->setGroupBundle('entity_test_group_type')
      ->grantPermission('access content')
      ->grantPermission('administer group')
      // Random permission to test that queries are working properly when
      // requesting a subset of permissions.
      ->grantPermission('edit any group entity_test')
      ->save();

    $roles = $this->ogRoleManager->getRolesByPermissions(['access content']);
    $this->assertCount(3, $roles);

    // Filter based on the entity type id and bundle.
    $roles = $this->ogRoleManager->getRolesByPermissions(['administer group'], 'entity_test', 'entity_test_group_type');
    $this->assertCount(1, $roles);
    $actual_role = reset($roles);
    $this->assertEquals($actual_role->id(), $og_role3->id());

    // By default, roles are that match all of the passed permissions.
    $roles = $this->ogRoleManager->getRolesByPermissions([
      'access content',
      'administer group',
    ]);
    $this->assertCount(2, $roles);

    // Request roles that match one or more of the passed permissions.
    $roles = $this->ogRoleManager->getRolesByPermissions([
      'access content',
      'administer group',
    ], NULL, NULL, FALSE);
    $this->assertCount(3, $roles);

    // Require roles with all of the passed permissions and in certain entity
    // type ID and bundle.
    $roles = $this->ogRoleManager->getRolesByPermissions([
      'access content',
      'administer group',
    ], 'entity_test', 'entity_test_group_type', TRUE);
    $this->assertCount(1, $roles);
  }

}
