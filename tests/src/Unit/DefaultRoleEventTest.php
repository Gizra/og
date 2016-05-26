<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\Event\DefaultRoleEvent;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\Event\DefaultRoleEvent
 */
class DefaultRoleEventTest extends UnitTestCase {

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testGetRole($roles) {
    $event = new DefaultRoleEvent();
    $event->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $event->getRole($name));
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getRoles
   * @covers ::setRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testGetRoles($roles) {
    $event = new DefaultRoleEvent();
    $event->setRoles($roles);

    $actual_roles = $event->getRoles();
    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $actual_roles[$name]);
    }

    $this->assertEquals(count($roles), count($actual_roles));
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::addRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testAddRole($roles) {
    $event = new DefaultRoleEvent();
    foreach ($roles as $name => $role) {
      $this->assertFalse($event->hasRole($name));
      $event->addRole($name, $role);
      $this->assertRoleEquals($role, $event->getRole($name));

      // Adding a role a second time should throw an exception.
      try {
        $event->addRole($name, $role);
        $this->fail('It should not be possible to add a role with the same name a second time.');
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::addRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testAddRoles($roles) {
    $event = new DefaultRoleEvent();
    $event->addRoles($roles);

    $actual_roles = $event->getRoles();
    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $actual_roles[$name]);
    }

    $this->assertEquals(count($roles), count($actual_roles));
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::setRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testSetRole($roles) {
    $event = new DefaultRoleEvent();
    foreach ($roles as $name => $role) {
      $this->assertFalse($event->hasRole($name));
      $event->setRole($name, $role);
      $this->assertRoleEquals($role, $event->getRole($name));

      // Setting a role a second time should be possible. No exception should be
      // thrown.
      $event->setRole($name, $role);
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::deleteRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testDeleteRole($roles) {
    $event = new DefaultRoleEvent();
    $event->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertTrue($event->hasRole($name));
      $event->deleteRole($name);
      $this->assertFalse($event->hasRole($name));
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::hasRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testHasRole($roles) {
    $event = new DefaultRoleEvent();
    foreach ($roles as $name => $role) {
      $this->assertFalse($event->hasRole($name));
      $event->addRole($name, $role);
      $this->assertTrue($event->hasRole($name));
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetGet
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetGet($roles) {
    $event = new DefaultRoleEvent();
    $event->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $event[$name]);
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetSet
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetSet($roles) {
    $event = new DefaultRoleEvent();

    foreach ($roles as $name => $role) {
      $this->assertFalse($event->hasRole($name));
      $event[$name] = $role;
      $this->assertRoleEquals($role, $event->getRole($name));
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetUnset
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetUnset($roles) {
    $event = new DefaultRoleEvent();
    $event->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertTrue($event->hasRole($name));
      unset($event[$name]);
      $this->assertFalse($event->hasRole($name));
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetExists
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetExists($roles) {
    $event = new DefaultRoleEvent();
    foreach ($roles as $name => $role) {
      $this->assertFalse(isset($event[$name]));
      $event->addRole($name, $role);
      $this->assertTrue(isset($event[$name]));
    }
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getIterator
   *
   * @dataProvider defaultRoleProvider
   */
  public function testIteratorAggregate($roles) {
    $event = new DefaultRoleEvent();
    $event->setRoles($roles);

    foreach ($event as $name => $role) {
      $this->assertRoleEquals($roles[$name], $role);
      unset($roles[$name]);
    }

    // Verify that all roles were iterated over.
    $this->assertEmpty($roles);
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::addRole
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testAddInvalidRole($invalid_roles) {
    $event = new DefaultRoleEvent();
    try {
      foreach ($invalid_roles as $name => $invalid_role) {
        $event->addRole($name, $invalid_role);
      }
      $this->fail('An invalid role cannot be added.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result. Do an arbitrary assertion so the test is not marked as
      // risky.
      $this->assertTrue(TRUE);
    }
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::addRoles
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testAddInvalidRoles($invalid_roles) {
    $event = new DefaultRoleEvent();
    try {
      $event->addRoles($invalid_roles);
      $this->fail('An array of invalid roles cannot be added.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result. Do an arbitrary assertion so the test is not marked as
      // risky.
      $this->assertTrue(TRUE);
    }
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::setRole
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testSetInvalidRole($invalid_roles) {
    $event = new DefaultRoleEvent();
    try {
      foreach ($invalid_roles as $name => $invalid_role) {
        $event->setRole($name, $invalid_role);
      }
      $this->fail('An invalid role cannot be set.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result. Do an arbitrary assertion so the test is not marked as
      // risky.
      $this->assertTrue(TRUE);
    }
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::setRoles
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testSetInvalidRoles($invalid_roles) {
    $event = new DefaultRoleEvent();
    try {
      $event->setRoles($invalid_roles);
      $this->fail('An array of invalid roles cannot be set.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result. Do an arbitrary assertion so the test is not marked as
      // risky.
      $this->assertTrue(TRUE);
    }
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::offsetSet
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testInvalidOffsetSet($invalid_roles) {
    $event = new DefaultRoleEvent();
    try {
      foreach ($invalid_roles as $name => $invalid_role) {
        $event[$name] = $invalid_role;
      }
      $this->fail('An invalid role cannot be set through ArrayAccess.');
    }
    catch (\InvalidArgumentException $e) {
      // Expected result. Do an arbitrary assertion so the test is not marked as
      // risky.
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Provides test data to test default roles.
   *
   * @return array
   *   An array of test data arrays, each test data array containing an array of
   *   test default roles, keyed by default role name.
   */
  public function defaultRoleProvider() {
    return [
      // Test adding a single administrator role with only a label.
      [
        [
          OgRoleInterface::ADMINISTRATOR => ['label' => $this->t('Administrator')],
        ],
      ],
      // Test adding a single administrator role with a label and role type.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'label' => $this->t('Administrator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
        ],
      ],
      // Test adding multiple roles.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'label' => $this->t('Administrator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
          'moderator' => [
            'label' => $this->t('Moderator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_STANDARD,
          ],
          'contributor' => [
            'label' => $this->t('Contributor'),
          ],
        ],
      ],
    ];
  }

  /**
   * Provides invalid test data to test default roles.
   *
   * @return array
   *   An array of test data arrays, each test data array containing an array of
   *   invalid test default roles, keyed by default role name.
   */
  public function invalidDefaultRoleProvider() {
    return [
      // A role with a missing name.
      [
        [
          '' => ['label' => $this->t('Administrator')],
        ],
      ],
      // A role without a label.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
        ],
      ],
      // A role with an invalid role type.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'label' => $this->t('Administrator'),
            'role_type' => 'Some non-existing role type',
          ],
        ],
      ],
      // An array of multiple correct roles, with one invalid role type sneaked
      // in.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'label' => $this->t('Administrator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
          'moderator' => [
            'label' => $this->t('Moderator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_STANDARD,
          ],
          'contributor' => [
            'label' => $this->t('Contributor'),
            'role_type' => 'Some non-existing role type',
          ],
        ],
      ],
    ];
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

  /**
   * Asserts that the given role properties matches the expected result.
   *
   * @param array $expected
   *   An array of expected role properties.
   * @param array $actual
   *   An array of actual role properties.
   */
  protected function assertRoleEquals(array $expected, array $actual) {
    // Provide default value for the role type.
    if (empty($expected['role_type'])) {
      $expected['role_type'] = OgRoleInterface::ROLE_TYPE_STANDARD;
    }
    $this->assertEquals($expected, $actual);
  }

}
