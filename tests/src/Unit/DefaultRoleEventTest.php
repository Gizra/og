<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Entity\OgRole;
use Drupal\og\Event\DefaultRoleEvent;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
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
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testGetRole($roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $this->defaultRoleEvent->getRole($name));
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
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    $actual_roles = $this->defaultRoleEvent->getRoles();
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
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::addRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testAddRoles($roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->addRoles($roles);

    $actual_roles = $this->defaultRoleEvent->getRoles();
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
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::deleteRole
   *
   * @dataProvider defaultRoleProvider
   */
  public function testDeleteRole($roles) {
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertTrue($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent->deleteRole($name);
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
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
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent->addRole($role);
      $this->assertTrue($this->defaultRoleEvent->hasRole($name));
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
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $this->defaultRoleEvent[$name]);
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
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
      $this->defaultRoleEvent[$name] = $role;
      $this->assertRoleEquals($role, $this->defaultRoleEvent->getRole($name));
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
    $this->expectOgRoleCreation($roles);

    $this->defaultRoleEvent->setRoles($roles);

    foreach ($roles as $name => $role) {
      $this->assertTrue($this->defaultRoleEvent->hasRole($name));
      unset($this->defaultRoleEvent[$name]);
      $this->assertFalse($this->defaultRoleEvent->hasRole($name));
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
    $this->expectOgRoleCreation($roles);

    foreach ($roles as $name => $role) {
      $this->assertFalse(isset($this->defaultRoleEvent[$name]));
      $this->defaultRoleEvent->addRole($role);
      $this->assertTrue(isset($this->defaultRoleEvent[$name]));
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
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::addRole
   *
   * @dataProvider invalidDefaultRoleProvider
   */
  public function testAddInvalidRole($invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    try {
      foreach ($invalid_roles as $name => $invalid_role) {
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
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::addRoles
   *
   * @dataProvider invalidDefaultRoleProvider
   * @expectedException \InvalidArgumentException
   */
  public function testAddInvalidRoles($invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    $this->defaultRoleEvent->addRoles($invalid_roles);
    $this->fail('An array of invalid roles cannot be added.');
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::setRole
   *
   * @dataProvider invalidDefaultRoleProvider
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidRole($invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    foreach ($invalid_roles as $name => $invalid_role) {
      $this->defaultRoleEvent->setRole($invalid_role);
    }
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::setRoles
   *
   * @dataProvider invalidDefaultRoleProvider
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidRoles($invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    $this->defaultRoleEvent->setRoles($invalid_roles);
  }

  /**
   * @param array $invalid_roles
   *   An array of invalid test default roles.
   *
   * @covers ::offsetSet
   *
   * @dataProvider invalidDefaultRoleProvider
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidOffsetSet($invalid_roles) {
    $this->expectOgRoleCreation($invalid_roles);

    foreach ($invalid_roles as $name => $invalid_role) {
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
      // A role without a label.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRole::ADMINISTRATOR,
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
        ],
      ],
      // A role with an invalid role type.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRole::ADMINISTRATOR,
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
   * @param \Drupal\og\Entity\OgRole $actual
   *   The actual OgRole entity to check. Note that we are not specifying the
   *   OgRoleInterface type because of a PHP 5 class inheritance limitation.
   */
  protected function assertRoleEquals(array $expected, OgRole $actual) {
    // Provide default values.
    $this->addDefaultRoleProperties($expected);
    foreach (['name', 'label', 'role_type', 'is_admin'] as $property) {
      $this->assertEquals($expected[$property], $actual->get($property));
    }
  }

  /**
   * Adds an expectation that roles with the given properties should be created.
   *
   * @param array $roles
   *   An array of role properties that are expected to be passed to the roles
   *   that should be created.
   */
  protected function expectOgRoleCreation($roles) {
    foreach ($roles as $properties) {
      // Provide default values.
      $this->addDefaultRoleProperties($properties);
      $og_role = new OgRole($properties, 'og_role');
      $this->ogRoleStorage->create($properties)->willReturn($og_role);
    }
  }

  /**
   * Enriches the passed in role properties with default properties.
   *
   * @param array $properties
   *   The role properties to enrich.
   */
  protected function addDefaultRoleProperties(&$properties) {
    $properties += [
      'role_type' => OgRoleInterface::ROLE_TYPE_STANDARD,
      'is_admin' => FALSE,
    ];
  }

}
