<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Group permissions tests.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\GroupPermission
 */
class GroupPermissionTest extends UnitTestCase {

  /**
   * Tests getting roles.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getApplicableRoles
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGetRoles(array $values) {
    $permission = new GroupPermission($values);
    $this->assertEquals($values['roles'], $permission->getApplicableRoles());
  }

  /**
   * Tests setting roles.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setApplicableRoles
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSetRoles(array $values) {
    $permission = new GroupPermission();
    $permission->setApplicableRoles($values['roles']);
    $this->assertEquals($values['roles'], $permission->get('roles'));
  }

  /**
   * Tests getting a property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::get
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGet(array $values) {
    $permission = new GroupPermission($values);
    foreach ($values as $property => $value) {
      $this->assertEquals($value, $permission->get($property));
    }
  }

  /**
   * Tests setting a property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::set
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSet(array $values) {
    $permission = new GroupPermission();
    foreach ($values as $property => $value) {
      $permission->set($property, $value);
      $this->assertEquals($value, $permission->get($property));
    }
  }

  /**
   * Tests getting the name property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getName
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGetName(array $values) {
    $permission = new GroupPermission($values);
    $this->assertEquals($values['name'], $permission->getName());
  }

  /**
   * Tests setting the name property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setName
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSetName(array $values) {
    $permission = new GroupPermission();
    $permission->setName($values['name']);
    $this->assertEquals($values['name'], $permission->get('name'));
  }

  /**
   * Tests getting the title property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getTitle
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGetTitle(array $values) {
    $permission = new GroupPermission($values);
    $this->assertEquals($values['title'], $permission->getTitle());
  }

  /**
   * Tests setting the title property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setTitle
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSetTitle(array $values) {
    $permission = new GroupPermission();
    $permission->setTitle($values['title']);
    $this->assertEquals($values['title'], $permission->get('title'));
  }

  /**
   * Tests getting the description property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getDescription
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGetDescription(array $values) {
    $permission = new GroupPermission($values);
    $this->assertEquals($values['description'], $permission->getDescription());
  }

  /**
   * Tests setting the description property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setDescription
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSetDescription(array $values) {
    $permission = new GroupPermission();
    $permission->setDescription($values['description']);
    $this->assertEquals($values['description'], $permission->get('description'));
  }

  /**
   * Tests getting the default roles assigned to a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getDefaultRoles
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGetDefaultRoles(array $values) {
    $permission = new GroupPermission($values);
    $this->assertEquals($values['default roles'], $permission->getDefaultRoles());
  }

  /**
   * Tests setting the default roles assigned to a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setDefaultRoles
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSetDefaultRoles(array $values) {
    $permission = new GroupPermission();
    $permission->setDefaultRoles($values['default roles']);
    $this->assertEquals($values['default roles'], $permission->get('default roles'));
  }

  /**
   * Tests getting the restrict access property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getRestrictAccess
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGetRestrictAccess(array $values) {
    $permission = new GroupPermission($values);
    $this->assertEquals($values['restrict access'], $permission->getRestrictAccess());
  }

  /**
   * Tests setting the restrict access property of a permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setRestrictAccess
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSetRestrictAccess(array $values) {
    $permission = new GroupPermission();
    $permission->setRestrictAccess($values['restrict access']);
    $this->assertEquals($values['restrict access'], $permission->get('restrict access'));
  }

  /**
   * Tests getting an invalid property of a permission.
   *
   * @covers ::get
   */
  public function testGetInvalidProperty() {
    $permission = new GroupPermission();

    $this->expectException(\InvalidArgumentException::class);
    $permission->get('invalid property');
  }

  /**
   * Tests setting an invalid property for a permission.
   *
   * @covers ::set
   */
  public function testSetInvalidProperty() {
    $permission = new GroupPermission();

    $this->expectException(\InvalidArgumentException::class);
    $permission->set('invalid property', 'a value');
  }

  /**
   * Tests setting an invalid restrict access value for  a permission.
   *
   * @covers ::set
   */
  public function testSetInvalidRestrictAccessValue() {
    $permission = new GroupPermission();

    $this->expectException(\InvalidArgumentException::class);
    $permission->set('restrict access', 'invalid value');
  }

  /**
   * Data provider; Return permissions.
   *
   * @return array
   *   An array of test data, each data set consisting of an associative array
   *   of permission values, keyed by property name.
   */
  public function groupPermissionProvider() {
    return [
      [
        [
          'name' => 'edit own article content',
          'title' => $this->t('Article: Edit own content'),
          'description' => $this->t('Allows to update own article content'),
          'default roles' => [OgRoleInterface::ADMINISTRATOR],
          'restrict access' => FALSE,
          'roles' => [OgRoleInterface::ADMINISTRATOR],
        ],
      ],
    ];
  }

  /**
   * Mocked string translation method.
   *
   * @param string $string
   *   The string to be translated.
   *
   * @return string
   *   The same string. For this test it is not important whether the string is
   *   correctly translated or not.
   */
  protected function t($string) {
    return $string;
  }

}
