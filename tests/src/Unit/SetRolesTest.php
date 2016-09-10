<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests set role method from the og membership.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Entity\OgMembership
 */
class SetRolesTest extends UnitTestCase {

  /**
   * OG roles service.
   *
   * @var \Drupal\og\Entity\OgRole|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRole;

  /**
   * OG membership service.
   *
   * @var \Drupal\og\Entity\OgMembership|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogMembership;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->ogRole = $this->prophesize(OgRole::class);
    $this->ogMembership = $this->prophesize(OgMembership::class);
  }

  /**
   * Tests setting OG roles .
   *
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::setRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testSetRoles($roles) {
    $this->expectOgRoleCreation($roles);

    $this->ogMembership->setRoles($roles);

    $actual_roles = $this->ogMembership->getRoles();
    foreach ($roles as $name => $role) {
      $this->assertRoleEquals($role, $actual_roles[$name]);
    }

    $this->assertEquals(count($roles), count($actual_roles));
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
            'label' => 'Administrator',
          ],
        ],
      ],
      // Test adding a single administrator role with a label and role type.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRoleInterface::ADMINISTRATOR,
            'label' => 'Administrator',
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
        ],
      ],
      // Test adding multiple roles.
      [
        [
          OgRoleInterface::ADMINISTRATOR => [
            'name' => OgRoleInterface::ADMINISTRATOR,
            'label' => 'Administrator',
            'role_type' => OgRoleInterface::ROLE_TYPE_REQUIRED,
          ],
          'moderator' => [
            'name' => 'moderator',
            'label' => 'Moderator',
            'role_type' => OgRoleInterface::ROLE_TYPE_STANDARD,
          ],
          'contributor' => [
            'name' => 'contributor',
            'label' => 'Contributor',
          ],
        ],
      ],
    ];
  }

}
