<?php

namespace Drupal\Tests\og\Unit;

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
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::getRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testGetRoles($roles) {
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
  }

  /**
   * @param array $roles
   *   An array of test default roles.
   *
   * @covers ::setRoles
   *
   * @dataProvider defaultRoleProvider
   */
  public function testSetRoles($roles) {
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

}
