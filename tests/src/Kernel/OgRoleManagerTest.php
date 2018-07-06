<?php

namespace Drupal\Tests\og\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;

/**
 * Tests deletion of orphaned group content and memberships.
 *
 * @group og
 */
class OgRoleManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'field',
    'node',
    'og',
    'options',
  ];

  /**
   * A test group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

  /**
   * A test group bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * A test Og role name.
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
    $this->installEntitySchema('node');
    $this->bundle = mb_strtolower($this->randomMachineName());
    $this->roleName = mb_strtolower($this->randomMachineName());

    $this->ogRoleManager = $this->container->get('og.role_manager');

    // Create a group entity type.
    NodeType::create([
      'type' => $this->bundle,
      'name' => $this->randomString(),
    ])->save();
    Og::groupTypeManager()->addGroup('node', $this->bundle);

    // Create a custom role to verify that the tests covers custom roles as
    // well.
    $og_role = OgRole::create();
    $og_role
      ->setName($this->roleName)
      ->setLabel($this->randomString())
      ->setGroupType('node')
      ->setGroupBundle($this->bundle)
      ->grantPermission('administer group')
      ->save();
  }

  /**
   * Tests that all roles are loaded for a certain group.
   */
  public function testGetRolesByBundle() {
    $expected_role_ids = [
      'node-' . $this->bundle . '-administrator',
      'node-' . $this->bundle . '-member',
      'node-' . $this->bundle . '-non-member',
      'node-' . $this->bundle . '-' . $this->roleName,
    ];

    $roles = $this->ogRoleManager->getRolesByBundle('node', $this->bundle);
    $role_ids = array_keys($roles);
    sort($expected_role_ids);
    sort($role_ids);
    $this->assertEquals($expected_role_ids, $role_ids);
  }

}
