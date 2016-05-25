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
   * Provides test data to test default roles.
   *
   * @return array
   *   An array of test data arrays, each test data array containing an array of
   *   test default roles, keyed by default role name.
   */
  public function defaultRoleProvider() {
    return [
      [
        OgRoleInterface::ADMINISTRATOR => ['label' => $this->t('Administrator')],
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
