<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\Event\DefaultRoleEvent;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests default role events.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Event\DefaultRoleEvent
 */
class DefaultRoleEventTest extends UnitTestCase {

  /**
   * The DefaultRoleEvent class, which is the system under test.
   *
   * @var \Drupal\og\Event\DefaultRoleEvent
   */
  protected $defaultRoleEvent;

  /**
   * The mocked OgRole entity storage.
   *
   * @var \Drupal\core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRoleStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->ogRoleStorage = $this->prophesize(EntityStorageInterface::class);
    $entity_type_manager->getStorage('og_role')->willReturn($this->ogRoleStorage->reveal());
    $this->defaultRoleEvent = new DefaultRoleEvent($entity_type_manager->reveal());
  }

  /**
   * Tests getting OG roles from the default role event.
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testGetRole(array $roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $this->defaultRoleEvent->getRole($name));
    }
  }

  /**
   * Tests getting OG roles from the default role event.
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getRoles
   * @covers ::setRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testGetRoles(array $roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    $actual_roles = $this->defaultRoleEvent->getRoles();
    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $actual_roles[$name]);
    }

    $this->assertEquals(count($roles), count($actual_roles));
  }

  /**
   * Tests adding an OG role to the default role event.
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::addRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testAddRole(array $roles) {
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent->addRole($role);
      $this->assertRoleEquals($role, $this->defaultRoleEvent->getRole($name));

      // Adding a role a second time should throw an exception.
      try {
        $this->defaultRoleEvent->addRole($role);
        $this->fail('It should not be possible to add a role with the same name a second time.');
      }
      catch (\InvalidArgumentException $e) {
        // Expected result.
      }
    }
  }

  /**
   * Tests adding OG roles to the default role event.
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::addRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testAddRoles(array $roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->addRoles($roles);

    $actual_roles = $this->defaultRoleEvent->getRoles();
    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $actual_roles[$name]);
    }

    $this->assertEquals(count($roles), count($actual_roles));
  }

  /**
   * Tests setting OG roles for the default role event.
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::setRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testSetRole(array $roles) {
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent->setRole($role);
      $this->assertRoleEquals($role, $this->defaultRoleEvent->getRole($name));

      // Setting a role a second time should be possible. No exception should be
      // thrown.
      $this->defaultRoleEvent->setRole($role);
    }
  }

  /**
   * Tests deleting OG roles from the default role event.
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::deleteRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testDeleteRole(array $roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertTrue($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent->deleteRole($name);
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
    }
  }

  /**
   * Tests checking OG roles exist in a default role event.
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::hasRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testHasRole(array $roles) {
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent->addRole($role);
      $this->assertTrue($this->defaultRoleEvent->hasRole($name));
    }
  }

  /**
   * Tests "testOffsetGet".
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetGet
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetGet(array $roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $this->defaultRoleEvent[$name]);
    }
  }

  /**
   * Tests "offsetSet".
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetSet
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetSet(array $roles) {
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent[$name] = $role;
      $this->assertRoleEquals($role, $this->defaultRoleEvent->getRole($name));
    }
  }

  /**
   * Tests "testOffsetUnset".
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetUnset
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetUnset(array $roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertTrue($this->defaultRoleEvent->hasRole($name));
      unset($this->defaultRoleEvent[$name]);
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
    }
  }

  /**
   * Tests "testOffsetExists".
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::offsetExists
   *
   * @dataProvider defaultRoleProvider
   */
  public function testOffsetExists(array $roles) {
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse(isset($this->defaultRoleEvent[$name]));
      $this->defaultRoleEvent->addRole($role);
      $this->assertTrue(isset($this->defaultRoleEvent[$name]));
    }
  }

  /**
   * Tests "testIteratorAggregate".
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getIterator
   *
   * @dataProvider defaultRoleProvider
   */
  public function testIteratorAggregate(array $roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($this->defaultRoleEvent as $name => $role) {
      $this->assertRoleEquals($roles[$name], $role);
      unset($roles[$name]);
    }

    // Verify that all roles were iterated over.
    $this->assertEmpty($roles);
  }

  /**
   * Tests adding an invalid OG role to the default role event.
   *
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::addRole
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testAddInvalidRole(array $invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    try {
      foreach ($invalid_roles as $invalid_role) {
        $this->defaultRoleEvent->addRole($invalid_role);
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
   * Tests adding invalid OG roles to the default role event.
   *
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::addRoles
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testAddInvalidRoles(array $invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    $this->expectException(\InvalidArgumentException::class);
    $this->defaultRoleEvent->addRoles($invalid_roles);
    $this->fail('An array of invalid roles cannot be added.');
  }

  /**
   * Tests setting an invalid OG role for the default role event.
   *
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::setRole
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testSetInvalidRole(array $invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    $this->expectException(\InvalidArgumentException::class);
    foreach ($invalid_roles as $invalid_role) {
      $this->defaultRoleEvent->setRole($invalid_role);
    }
  }

  /**
   * Tests setting invalid OG roles for the default role event.
   *
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::setRoles
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testSetInvalidRoles(array $invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    $this->expectException(\InvalidArgumentException::class);
    $this->defaultRoleEvent->setRoles($invalid_roles);
  }

  /**
   * Tests "offsetSet".
   *
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::offsetSet
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testInvalidOffsetSet(array $invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    foreach ($invalid_roles as $name => $invalid_role) {
      $this->expectException(\InvalidArgumentException::class);
      $this->defaultRoleEvent[$name] = $invalid_role;
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
      // Test adding a single administrator role with only the required
      // properties.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRoleInterface::ADMINISTRATOR,
            'label' => $this->t('Administrator'),
          ],
        ],
      ],
      // Test adding a single administrator role with a label and role type.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRoleInterface::ADMINISTRATOR,
            'label' => $this->t('Administrator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
        ],
      ],
      // Test adding multiple roles.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRoleInterface::ADMINISTRATOR,
            'label' => $this->t('Administrator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
          'moderator' => [
            'name' => 'moderator',
            'label' => $this->t('Moderator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_STANDARD,
          ],
          'contributor' => [
            'name' => 'contributor',
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
      // An array of multiple correct roles, with one invalid role type sneaked
      // in.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRoleInterface::ADMINISTRATOR,
            'label' => $this->t('Administrator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
          'moderator' => [
            'name' => 'moderator',
            'label' => $this->t('Moderator'),
            'role_type' => OgRoleInterface::ROLE_TYPE_STANDARD,
          ],
          'role with missing name' => [
            'label' => $this->t('Invalid role'),
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
   * @param \Drupal\og\Entity\OgRole $expected
   *   The expected role.
   * @param \Drupal\og\Entity\OgRole $actual
   *   The actual OgRole entity to check.
   *
   *   Note that we are not specifying the OgRoleInterface type because of a PHP
   *   5 class inheritance limitation.
   */
  protected function assertRoleEquals(OgRole $expected, OgRole $actual) {
    foreach (['name', 'label', 'role_type', 'is_admin'] as $property) {
      $this->assertEquals($expected->get($property), $actual->get($property));
    }
  }

  /**
   * Adds an expectation that roles with the given properties should be created.
   *
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   An array of role properties that are expected to be passed to the roles
   *   that should be created.
   */
  protected function expectOgRoleCreation(array &$roles) {
    foreach ($roles as &$properties) {
      $role = new OgRole($properties);
      $properties = $role;
    }
  }

}
