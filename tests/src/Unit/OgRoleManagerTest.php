<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgRoleInterface;
use Drupal\og\OgRoleManager;
use Drupal\og\PermissionManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests create membership helper function.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\OgRoleManager
 */
class OgRoleManagerTest extends UnitTestCase {

  /**
   * The entity type ID of the test group.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID of the test group.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The entity storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $eventDispatcher;

  /**
   * The created OG role.
   *
   * @var \Drupal\og\Entity\OgRole|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRole;

  /**
   * The entity storage for OgRole entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogRoleStorage;

  /**
   * The permissions manager service.
   *
   * @var \Drupal\og\PermissionManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $permissionManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    $this->ogRole = $this->prophesize(OgRole::class);
    $this->ogRoleStorage = $this->prophesize(EntityStorageInterface::class);
    $this->permissionManager = $this->prophesize(PermissionManagerInterface::class);

    $this
      ->entityTypeManager
      ->getStorage('og_role')
      ->willReturn($this->ogRoleStorage->reveal());
  }

  /**
   * Tests creation of per bundle roles.
   *
   * @param string $role_name
   *   The name of the role being created.
   *
   * @covers ::createPerBundleRoles
   *
   * @dataProvider bundleRolesProvider
   */
  public function testCreatePerBundleRoles($role_name) {
    $entity_type_id = $this->entityTypeId;
    $bundle = $this->bundle;
    // The Prophecy mocking framework uses 'promises' for dynamically generating
    // mocks that return context dependent data. This works by dynamically
    // setting the expected behaviors in an anonymous function. Make sure the
    // mocks are available in the local scope so they can be passed to the
    // anonymous functions.
    $permission_manager = $this->permissionManager;

    // We have to use OgRole and not OgRoleInterface, due to inheritance issues,
    // where PHP doesn't allow OgRoleInterface to extend RoleInterface.
    $og_role = $this->ogRole;

    foreach ($this->getDefaultRoleProperties() as $properties) {
      // It is expected that the role will be created with default properties.
      $this->ogRoleStorage->create($properties)
        ->will(function () use ($entity_type_id, $bundle, $role_name, $og_role, $permission_manager) {
          // It is expected that the OG permissions that need to be populated on
          // the new role will be requested. We are not testing permissions here
          // so we can just return an empty array.
          $permission_manager->getDefaultGroupPermissions($entity_type_id, $bundle, $role_name)
            ->willReturn([])
            ->shouldBeCalled();

          // For each role that is created it is expected that the role name
          // will be retrieved, so that the role name can be used to filter the
          // permissions.
          $og_role->getName()
            ->willReturn($role_name)
            ->shouldBeCalled();

          // The group type, bundle and permissions will have to be set on the
          // new role.
          $og_role->setGroupType($entity_type_id)->shouldBeCalled();
          $og_role->setGroupBundle($bundle)->shouldBeCalled();
          return $og_role->reveal();
        })
        ->shouldBeCalled();

      // The role is expected to be saved.
      $og_role->save()
        ->willReturn(1)
        ->shouldBeCalled();
    }

    $og_role_manager = $this->getOgRoleManager();
    $og_roles = $og_role_manager->createPerBundleRoles($this->entityTypeId, $this->bundle);
    $this->assertCount(2, $og_roles);
  }

  /**
   * Provides test data to test bundle roles creation.
   *
   * @return array
   *   Array with the OG Role machine names.
   */
  public function bundleRolesProvider() {
    return [
      [OgRoleInterface::ANONYMOUS],
      [OgRoleInterface::AUTHENTICATED],
    ];
  }

  /**
   * Returns the expected properties of the default role with the given name.
   *
   * @return array
   *   The default properties.
   */
  protected function getDefaultRoleProperties() {
    return [
      OgRoleInterface::ANONYMOUS => [
        'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
        'label' => 'Non-member',
        'name' => OgRoleInterface::ANONYMOUS,
      ],
      OgRoleInterface::AUTHENTICATED => [
        'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
        'label' => 'Member',
        'name' => OgRoleInterface::AUTHENTICATED,
      ],
    ];
  }

  /**
   * Tests role removal.
   *
   * @covers ::removeRoles
   */
  public function testRoleRemoval() {
    $properties = [
      'group_type' => $this->entityTypeId,
      'group_bundle' => $this->bundle,
    ];
    $this->ogRoleStorage->loadByProperties($properties)
      ->willReturn([
        $this->ogRole->reveal(),
        $this->ogRole->reveal(),
        $this->ogRole->reveal(),
      ])
      ->shouldBeCalled();

    // It is expected that all roles will be deleted, so three delete() calls
    // will be made.
    $this->ogRole->delete()
      ->shouldBeCalledTimes(3);

    $og_role_manager = $this->getOgRoleManager();
    $og_role_manager->removeRoles($this->entityTypeId, $this->bundle);
  }

  /**
   * Return a new OG role manager object.
   *
   * @return \Drupal\og\OgRoleManagerInterface
   *   The initialized OG role manager.
   */
  protected function getOgRoleManager() {
    return new OgRoleManager(
      $this->entityTypeManager->reveal(),
      $this->eventDispatcher->reveal(),
      $this->permissionManager->reveal()
    );
  }

}
