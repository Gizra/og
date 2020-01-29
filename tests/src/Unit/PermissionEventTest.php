<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\Event\PermissionEvent;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests permission events.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Event\PermissionEvent
 */
class PermissionEventTest extends UnitTestCase {

  /**
   * Tests getting a single group permission.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::getPermission
   *
   * @dataProvider permissionsProvider
   */
  public function testGetPermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    // An exception should be thrown when trying to get a permission that
    // doesn't exist.
    foreach ($permissions as $name => $permission) {
      try {
        $event->getPermission($name);
        $this->fail('Calling ::getPermission() on a non-existing permission throws an exception.');
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }
    }

    // Test that it can retrieve the permissions correctly after they are set.
    $event->setPermissions($permissions);

    foreach ($permissions as $permission) {
      $this->assertEquals($permission, $event->getPermission($permission->getName()));
    }
  }

  /**
   * Tests getting a single group content permission.
   *
   * @param \Drupal\og\GroupContentOperationPermission[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::getGroupContentOperationPermission
   *
   * @dataProvider groupContentOperationPermissionsProvider
   */
  public function testGetGroupContentOperationPermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    // An exception should be thrown if the permission doesn't exist yet.
    foreach ($permissions as $permission) {
      try {
        $event->getGroupContentOperationPermission($permission->getEntityType(), $permission->getBundle(), $permission->getOperation(), $permission->getOwner());
        $this->fail('Calling ::getGroupContentOperationPermission() on a non-existing permission throws an exception.');
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }
    }

    // Test that the permissions can be retrieved once they are set.
    $event->setPermissions($permissions);

    foreach ($permissions as $permission) {
      $this->assertEquals($permission, $event->getGroupContentOperationPermission($permission->getEntityType(), $permission->getBundle(), $permission->getOperation(), $permission->getOwner()));
    }
  }

  /**
   * Tests getting group permissions.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::getPermissions
   * @covers ::setPermissions
   *
   * @dataProvider permissionsProvider
   */
  public function testGetPermissions(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    $this->assertEquals($permissions, $event->getPermissions());
  }

  /**
   * Tests setting group permissions.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::setPermission
   *
   * @dataProvider permissionsProvider
   */
  public function testSetPermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    foreach ($permissions as $permission) {
      $event->setPermission($permission);
    }

    $this->assertEquals($permissions, $event->getPermissions());
  }

  /**
   * Tests setting an invalid permission.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::setPermission
   *
   * @dataProvider invalidPermissionsProvider
   */
  public function testSetInvalidPermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    foreach ($permissions as $permission) {
      $this->expectException(\InvalidArgumentException::class);
      $event->setPermission($permission);
    }
  }

  /**
   * Tests deleting a permission.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::deletePermission
   *
   * @dataProvider permissionsProvider
   */
  public function testDeletePermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    foreach ($permissions as $name => $permission) {
      // Before we delete the permission, it should still be there.
      $this->assertTrue($event->hasPermission($name));

      // After we delete the permission, it should be gone.
      $event->deletePermission($name);
      $this->assertFalse($event->hasPermission($name));
    }
  }

  /**
   * Tests deleting a group content permission.
   *
   * @param \Drupal\og\GroupContentOperationPermission[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::deleteGroupContentOperationPermission
   *
   * @dataProvider groupContentOperationPermissionsProvider
   */
  public function testDeleteGroupContentOperationPermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    foreach ($permissions as $permission) {
      $name = $permission->getName();

      $permission_entity_type_id = $permission->getEntityType();
      $permission_bundle_id = $permission->getBundle();
      $permission_operation = $permission->getOperation();
      $permission_ownership = $permission->getOwner();

      // Before we delete the permission, it should still be there.
      $this->assertTrue($event->hasPermission($name));

      // After we delete the permission, it should be gone.
      $event->deleteGroupContentOperationPermission($permission_entity_type_id, $permission_bundle_id, $permission_operation, $permission_ownership);
      $this->assertFalse($event->hasPermission($name));
    }
  }

  /**
   * Tests checking if permission exists.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::hasPermission
   *
   * @dataProvider permissionsProvider
   */
  public function testHasPermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    foreach ($permissions as $name => $permission) {
      $this->assertFalse($event->hasPermission($name));
      $event->setPermission($permission);
      $this->assertTrue($event->hasPermission($name));
    }
  }

  /**
   * Tests checking if group content permission exists.
   *
   * @param \Drupal\og\GroupContentOperationPermission[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::hasGroupContentOperationPermission
   *
   * @dataProvider groupContentOperationPermissionsProvider
   */
  public function testHasGroupContentOperationPermission(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    foreach ($permissions as $permission) {
      $permission_entity_type_id = $permission->getEntityType();
      $permission_bundle_id = $permission->getBundle();
      $permission_operation = $permission->getOperation();
      $permission_ownership = $permission->getOwner();

      $this->assertFalse($event->hasGroupContentOperationPermission($permission_entity_type_id, $permission_bundle_id, $permission_operation, $permission_ownership));
      $event->setPermission($permission);
      $this->assertTrue($event->hasGroupContentOperationPermission($permission_entity_type_id, $permission_bundle_id, $permission_operation, $permission_ownership));
    }
  }

  /**
   * Tests getting a group entity type ID.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::getGroupEntityTypeId
   *
   * @dataProvider permissionsProvider
   */
  public function testGetEntityTypeId(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $this->assertEquals($entity_type_id, $event->getGroupEntityTypeId());
  }

  /**
   * Tests getting a group bundle ID.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::getGroupBundleId
   *
   * @dataProvider permissionsProvider
   */
  public function testGetBundleId(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $this->assertEquals($bundle_id, $event->getGroupBundleId());
  }

  /**
   * Tests getting group content bundle IDs.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::getGroupContentBundleIds
   *
   * @dataProvider permissionsProvider
   */
  public function testGetGroupContentBundleIds(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $this->assertEquals($group_content_bundle_ids, $event->getGroupContentBundleIds());
  }

  /**
   * Tests "offsetGet".
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::offsetGet
   *
   * @dataProvider permissionsProvider
   */
  public function testOffsetGet(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    foreach ($permissions as $name => $permission) {
      $this->assertEquals($permission, $event[$name]);
    }

    // Test that an exception is thrown when requesting a non-existing
    // permission.
    try {
      $event['some-non-existing-permission'];
      $this->fail('An exception is thrown when a non-existing permission is requested through ArrayAccess.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }
  }

  /**
   * Tests "offsetSet".
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::offsetSet
   *
   * @dataProvider permissionsProvider
   */
  public function testOffsetSet(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    foreach ($permissions as $name => $permission) {
      $this->assertFalse($event->hasPermission($name));
      $event[$name] = $permission;
      $this->assertEquals($permission, $event->getPermission($name));
    }
  }

  /**
   * Tests setting invalid permissions through ArrayAccess.
   *
   * @param string $key
   *   The key to use when setting the permission through ArrayAccess.
   * @param mixed $permission
   *   A test value to set through ArrayAccess.
   *
   * @dataProvider offsetSetInvalidPermissionProvider
   */
  public function testOffsetSetInvalidPermission($key, $permission) {
    $this->expectException(\InvalidArgumentException::class);

    // phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
    $event = new PermissionEvent($this->randomMachineName(), $this->randomMachineName(), []);
    $event[$key] = $permission;
    // phpcs:enable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
  }

  /**
   * Data provider for testOffsetSetInvalidPermission().
   *
   * @return array
   *   An array of invalid test data, each set containing:
   *   - The array key to use when setting the permission though ArrayAccess.
   *   - The permission to set.
   */
  public function offsetSetInvalidPermissionProvider() {
    return [
      // Test that an exception is thrown when setting a nameless permission.
      [
        '',
        new GroupPermission([
          'title' => $this->t('A permission without a machine name.'),
        ]),
      ],

      // Test that an exception is thrown when setting permission without a
      // title.
      [
        '',
        new GroupPermission([
          'name' => 'a titleless permission',
        ]),
      ],
      // Test that an exception is thrown when the array key doesn't match the
      // machine name.
      [
        'a non-matching key',
        new GroupPermission([
          'name' => 'a different key',
          'title' => $this->t('This permission has a name that differs from the array key that is used to set it.'),
        ]),
      ],
      // Test that an exception is thrown when an object is passed which is not
      // implementing \Drupal\og\PermissionInterface.
      [
        'a non-matching key',
        new \stdClass(),
      ],
    ];
  }

  /**
   * Tests "offsetUnset".
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::offsetUnset
   *
   * @dataProvider permissionsProvider
   */
  public function testOffsetUnset(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    foreach ($permissions as $name => $permission) {
      $this->assertTrue($event->hasPermission($name));
      unset($event[$name]);
      $this->assertFalse($event->hasPermission($name));
    }

    // Test that it is possible to unset a non-existing permissions. In keeping
    // with standard PHP practices this should not throw any error.
    unset($event['some-non-existing-permission']);
  }

  /**
   * Tests "offsetExists".
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::offsetExists
   *
   * @dataProvider permissionsProvider
   */
  public function testOffsetExists(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    foreach ($permissions as $name => $permission) {
      $this->assertFalse(isset($event[$name]));
      $event->setPermission($permission);
      $this->assertTrue(isset($event[$name]));
    }
  }

  /**
   * Check that the iterator has looped over all permissions correctly.
   *
   * @param \Drupal\og\PermissionInterface[] $permissions
   *   An array of test permissions.
   * @param string $entity_type_id
   *   The entity type ID of the group type to which the permissions apply.
   * @param string $bundle_id
   *   The bundle ID of the group type to which the permissions apply.
   * @param array $group_content_bundle_ids
   *   An array of group content bundle IDs to which the permissions apply,
   *   keyed by group content entity type ID.
   *
   * @covers ::getIterator
   *
   * @dataProvider permissionsProvider
   */
  public function testIteratorAggregate(array $permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    foreach ($event as $name => $permission) {
      $expected_permission = reset($permissions);
      $expected_name = key($permissions);
      $this->assertEquals($expected_name, $name);
      $this->assertEquals($expected_permission, $permission);
      array_shift($permissions);
    }

    // Check that the iterator has looped over all permissions correctly.
    $this->assertEmpty($permissions);
  }

  /**
   * Tests creation of an invalid operation permission.
   */
  public function testInvalidGroupContentOperationPermissionCreation() {
    // An exception should be thrown when a group content operation permission
    // is created with an invalid owner type.
    $this->expectException(\InvalidArgumentException::class);
    new GroupContentOperationPermission([
      'name' => 'invalid permission',
      'title' => $this->t('This is an invalid permission.'),
      'entity type' => 'node',
      'bundle' => 'article',
      'operation' => 'create',
      'owner' => 'an invalid owner',
    ]);
  }

  /**
   * Tests that it is possible to overwrite an existing operation permission.
   *
   * Operation permissions are not identified by their machine name, but by the
   * unique combination of entity type, bundle, operation and ownership. It
   * should be possible to change the name, title and other properties.
   */
  public function testOverwritingGroupContentOperationPermission() {
    $event = new PermissionEvent($this->randomMachineName(), $this->randomMachineName(), []);

    $entity_type_id = $this->randomMachineName();
    $bundle_id = $this->randomMachineName();
    $operation = $this->randomMachineName();
    $ownership = TRUE;

    $original_permission = new GroupContentOperationPermission([
      'name' => 'the original permission',
      'title' => $this->t('This is the original permission.'),
      'description' => $this->t('This is the original description.'),
      'entity type' => $entity_type_id,
      'bundle' => $bundle_id,
      'operation' => $operation,
      'owner' => $ownership,
      'default roles' => [OgRoleInterface::ADMINISTRATOR],
      'restrict access' => TRUE,
    ]);

    $altered_permission = new GroupContentOperationPermission([
      'name' => 'the altered permission',
      'title' => $this->t('This is the altered permission.'),
      'description' => $this->t('This is the altered description.'),
      'entity type' => $entity_type_id,
      'bundle' => $bundle_id,
      'operation' => $operation,
      'owner' => $ownership,
      'default roles' => [OgRoleInterface::AUTHENTICATED],
      'restrict access' => FALSE,
    ]);

    // Check that the original permission can be set and retrieved.
    $event->setPermission($original_permission);
    $this->assertEquals($original_permission, $event->getGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $ownership));

    // Check that the altered permission can be set and retrieved, even though
    // it does not have the same machine name as the original permission.
    $event->setPermission($altered_permission);
    $this->assertEquals($altered_permission, $event->getGroupContentOperationPermission($entity_type_id, $bundle_id, $operation, $ownership));
  }

  /**
   * Data provider to test permissions.
   *
   * @return array
   *   An array of test data arrays, each test data array containing:
   *   - An array of test permissions, keyed by permission ID.
   *   - The entity type ID of the group type to which these permissions apply.
   *   - The bundle ID of the group type to which these permissions apply.
   *   - An array of group content bundle IDs to which these permissions apply,
   *     keyed by group content entity type ID.
   */
  public function permissionsProvider() {
    $permissions = [
      // A simple permission with only the required option.
      [
        [
          'appreciate nature' => new GroupPermission([
            'name' => 'appreciate nature',
            'title' => $this->t('Allows the member to go outdoors and appreciate the landscape.'),
          ]),
        ],
      ],
      // A single permission with restricted access and a default role.
      [
        [
          'administer group' => new GroupPermission([
            'name' => 'administer group',
            'title' => $this->t('Administer group'),
            'description' => $this->t('Manage group members and content in the group.'),
            'default roles' => [OgRoleInterface::ADMINISTRATOR],
            'restrict access' => TRUE,
          ]),
        ],
      ],
      // A permission restricted to a specific role, and having a default role.
      [
        [
          'unsubscribe' => new GroupPermission([
            'name' => 'unsubscribe',
            'title' => $this->t('Unsubscribe from group'),
            'description' => $this->t('Allow members to unsubscribe themselves from a group, removing their membership.'),
            'roles' => [OgRoleInterface::AUTHENTICATED],
            'default roles' => [OgRoleInterface::AUTHENTICATED],
          ]),
        ],
      ],
      // Simulate a subscriber providing multiple permissions.
      [
        [
          'subscribe' => new GroupPermission([
            'name' => 'subscribe',
            'title' => $this->t('Subscribe to group'),
            'description' => $this->t('Allow non-members to request membership to a group (approval required).'),
            'roles' => [OgRoleInterface::ANONYMOUS],
            'default roles' => [OgRoleInterface::ANONYMOUS],
          ]),
          'subscribe without approval' => new GroupPermission([
            'name' => 'subscribe without approval',
            'title' => $this->t('Subscribe to group (no approval required)'),
            'description' => $this->t('Allow non-members to join a group without an approval from group administrators.'),
            'roles' => [OgRoleInterface::ANONYMOUS],
          ]),
          'unsubscribe' => new GroupPermission([
            'name' => 'unsubscribe',
            'title' => $this->t('Unsubscribe from group'),
            'description' => $this->t('Allow members to unsubscribe themselves from a group, removing their membership.'),
            'roles' => [OgRoleInterface::AUTHENTICATED],
            'default roles' => [OgRoleInterface::AUTHENTICATED],
          ]),
        ],
      ],
      // A mix of GroupPermissions and GroupContentOperationPermissions.
      [
        [
          'appreciate nature' => new GroupPermission([
            'name' => 'appreciate nature',
            'title' => $this->t('Allows the member to go outdoors and appreciate the landscape.'),
          ]),
          'create article content' => new GroupContentOperationPermission([
            'name' => 'create article content',
            'title' => $this->t('Article: Create new content'),
            'entity type' => 'node',
            'bundle' => 'article',
            'operation' => 'create',
          ]),
        ],
      ],
    ];

    // Supply a random entity type ID, bundle ID and array of group content
    // bundle IDs for each data set.
    foreach ($permissions as &$item) {
      $item[] = $this->randomMachineName();
      $item[] = $this->randomMachineName();
      $item[] = [$this->randomMachineName() => [$this->randomMachineName()]];
    }

    return $permissions;
  }

  /**
   * Data provider to test handling of invalid permissions.
   *
   * @return array
   *   An array of test data arrays, each test data array containing:
   *   - An array of test permissions, keyed by permission ID.
   *   - The entity type ID of the group type to which these permissions apply.
   *   - The bundle ID of the group type to which these permissions apply.
   *   - An array of group content bundle IDs to which these permissions apply,
   *     keyed by group content entity type ID.
   */
  public function invalidPermissionsProvider() {
    $permissions = [
      // A permission without a machine name.
      [
        [
          new GroupPermission([
            'title' => $this->t('This permission does not have a title.'),
          ]),
        ],
      ],
      // A permission without a human readable title.
      [
        [
          new GroupPermission([
            'name' => 'invalid permission',
          ]),
        ],
      ],
      // A group content operation permission without a machine name.
      [
        [
          new GroupContentOperationPermission([
            'title' => $this->t('This is an invalid permission.'),
            'entity type' => 'node',
            'bundle' => 'article',
            'operation' => 'create',
          ]),
        ],
      ],
      // A group content operation permission without a human readable title.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'invalid permission',
            'entity type' => 'node',
            'bundle' => 'article',
            'operation' => 'create',
          ]),
        ],
      ],
      // A group content operation permission without an entity type ID.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'invalid permission',
            'title' => $this->t('This is an invalid permission.'),
            'bundle' => 'article',
            'operation' => 'create',
          ]),
        ],
      ],
      // A group content operation permission without a bundle.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'invalid permission',
            'title' => $this->t('This is an invalid permission.'),
            'entity type' => 'node',
            'operation' => 'create',
          ]),
        ],
      ],
      // A group content operation permission without an operation.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'invalid permission',
            'title' => $this->t('This is an invalid permission.'),
            'entity type' => 'node',
            'bundle' => 'article',
          ]),
        ],
      ],
      // A mix of correct and incorrect permissions.
      [
        [
          'subscribe' => new GroupPermission([
            'name' => 'subscribe',
            'title' => $this->t('Subscribe to group'),
            'description' => $this->t('Allow non-members to request membership to a group (approval required).'),
            'roles' => [OgRoleInterface::ANONYMOUS],
            'default roles' => [OgRoleInterface::ANONYMOUS],
          ]),
          'unsubscribe' => new GroupPermission([
            'name' => 'unsubscribe',
            'title' => $this->t('Unsubscribe from group'),
            'description' => $this->t('Allow members to unsubscribe themselves from a group, removing their membership.'),
            'roles' => [OgRoleInterface::AUTHENTICATED],
            'default roles' => [OgRoleInterface::AUTHENTICATED],
          ]),
          'permission' => new GroupPermission([
            'name' => 'invalid permission',
          ]),
        ],
      ],
    ];

    // Supply a random entity type ID, bundle ID and array of group content
    // bundle IDs for each data set.
    foreach ($permissions as &$item) {
      $item[] = $this->randomMachineName();
      $item[] = $this->randomMachineName();
      $item[] = [$this->randomMachineName() => [$this->randomMachineName()]];
    }

    return $permissions;
  }

  /**
   * Data provider to test group content operation permissions.
   *
   * @return array
   *   An array of test data arrays, each test data array containing:
   *   - An array of test permissions, keyed by permission ID.
   *   - The entity type ID of the group type to which these permissions apply.
   *   - The bundle ID of the group type to which these permissions apply.
   *   - An array of group content bundle IDs to which these permissions apply,
   *     keyed by group content entity type ID.
   */
  public function groupContentOperationPermissionsProvider() {
    $permissions = [
      // A simple permission with only the required parameters.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'paint yak wool',
            'title' => $this->t('Paint yak wool'),
            'entity type' => 'wool',
            'bundle' => 'yak fibre',
            'operation' => 'paint',
          ]),
        ],
      ],
      // A single permission with restricted access and a default role.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'shave any wild yak',
            'title' => $this->t('Shave any wild yaks'),
            'description' => $this->t('Whether the user has the right to shave wild yaks. This is usually limited to administrators since it is more dangerous than shaving domesticated yaks.'),
            'entity type' => 'yak',
            'bundle' => 'bos mutus',
            'operation' => 'shave',
            'default roles' => [OgRoleInterface::ADMINISTRATOR],
            'restrict access' => TRUE,
          ]),
        ],
      ],
      // A permission with an owner.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'shave own domesticated yak',
            'title' => $this->t('Shave own domesticated yaks'),
            'description' => $this->t('Whether the user has the right to their own domesticated yaks. This is granted by default to all members since it is expected that everyone knows how to take care of their own yaks.'),
            'entity type' => 'yak',
            'bundle' => 'bos grunniens',
            'operation' => 'shave',
            'owner' => TRUE,
            'default roles' => [
              OgRoleInterface::AUTHENTICATED,
              OgRoleInterface::ADMINISTRATOR,
            ],
          ]),
        ],
      ],
      // Simulate a subscriber providing multiple permissions.
      [
        [
          new GroupContentOperationPermission([
            'name' => 'spin any yak fibre',
            'title' => $this->t('Spin any yak fibre'),
            'entity type' => 'wool',
            'bundle' => 'yak fibre',
            'operation' => 'spin',
            'owner' => TRUE,
          ]),
          new GroupContentOperationPermission([
            'name' => 'weave own yak fibre',
            'title' => $this->t('Weave own yak fibre'),
            'entity type' => 'wool',
            'bundle' => 'yak fibre',
            'operation' => 'weave',
            'owner' => TRUE,
          ]),
          new GroupContentOperationPermission([
            'name' => 'dye any yak fibre',
            'title' => $this->t('Dye any yak fibre'),
            'entity type' => 'wool',
            'bundle' => 'yak fibre',
            'operation' => 'dye',
          ]),
        ],
      ],
    ];

    // Supply a random entity type ID, bundle ID and array of group content
    // bundle IDs for each data set.
    foreach ($permissions as &$item) {
      $item[] = $this->randomMachineName();
      $item[] = $this->randomMachineName();
      $item[] = [$this->randomMachineName() => [$this->randomMachineName()]];
    }

    return $permissions;
  }

  /**
   * Mock translation method.
   *
   * @param string $string
   *   The string to translate.
   *
   * @return string
   *   The translated string.
   */
  protected function t($string) {
    // Actually translating the strings is not important for this test.
    return $string;
  }

}
