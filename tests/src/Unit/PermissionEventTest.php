<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\Event\PermissionEvent;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\Event\PermissionEvent
 */
class PermissionEventTest extends UnitTestCase {

  /**
   * @param array $permissions
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
  public function testGetPermission($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    // An exception should be thrown when trying to get a permission that
    // doesn't exist.
    foreach ($permissions as $name => $permission) {
      try {
        $event->getPermission($name);
        $this->fail('Calling ::getPermission() on a non-existing permission throws an exception.');
      } catch (\InvalidArgumentException $e) {
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
   * @param array $permissions
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
  public function testGetPermissions($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    $this->assertEquals($permissions, $event->getPermissions());
  }

  /**
   * @param array $permissions
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
  public function testSetPermission($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    // Test that an exception is thrown when setting a nameless permission.
    try {
      $event->setPermission(new GroupPermission(['title' => 'A permission without a name']));
      $this->fail('An exception is thrown when a nameless permission is set.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }

    // Test that an exception is thrown when setting permission without a title.
    try {
      $event->setPermission(new GroupPermission(['name' => 'an-invalid-permission']));
      $this->fail('An exception is thrown when a permission without a title is set.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }

    foreach ($permissions as $permission) {
      $event->setPermission($permission);
    }

    $this->assertEquals($permissions, $event->getPermissions());
  }

  /**
   * @param array $permissions
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
  public function testDeletePermission($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
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
   * @param array $permissions
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
  public function testHasPermission($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    foreach ($permissions as $name => $permission) {
      $this->assertFalse($event->hasPermission($name));
      $event->setPermission($permission);
      $this->assertTrue($event->hasPermission($name));
    }
  }

  /**
   * @param array $permissions
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
  public function testGetEntityTypeId($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $this->assertEquals($entity_type_id, $event->getGroupEntityTypeId());
  }

  /**
   * @param array $permissions
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
  public function testGetBundleId($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $this->assertEquals($bundle_id, $event->getGroupBundleId());
  }

  /**
   * @param array $permissions
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
  public function testOffsetGet($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
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
   * @param array $permissions
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
  public function testOffsetSet($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    // Test that an exception is thrown when setting a nameless permission.
    try {
      $event[] = ['title' => 'A permission without a name'];
      $this->fail('An exception is thrown when a nameless permission is set through ArrayAccess.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }

    // Test that an exception is thrown when setting permission without a title.
    try {
      $event['an-invalid-permission'] = [];
      $this->fail('An exception is thrown when a permission without a title is set through ArrayAccess.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }

    foreach ($permissions as $name => $permission) {
      $this->assertFalse($event->hasPermission($name));
      $event[$name] = $permission;
      $this->assertEquals($permission, $event->getPermission($name));
    }
  }

  /**
   * @param array $permissions
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
  public function testOffsetUnset($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);
    $event->setPermissions($permissions);

    foreach ($permissions as $name => $permission) {
      $this->assertTrue($event->hasPermission($name));
      unset($event[$name]);
      $this->assertFalse($event->hasPermission($name));
    }

    // @todo
    // Test that an exception is thrown when unsetting a non-existing
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
   * @param array $permissions
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
  public function testOffsetExists($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
    $event = new PermissionEvent($entity_type_id, $bundle_id, $group_content_bundle_ids);

    foreach ($permissions as $name => $permission) {
      $this->assertFalse(isset($event[$name]));
      $event->setPermission($permission);
      $this->assertTrue(isset($event[$name]));
    }
  }

  /**
   * @param array $permissions
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
  public function testIteratorAggregate($permissions, $entity_type_id, $bundle_id, array $group_content_bundle_ids) {
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

