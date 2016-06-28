<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @group og
 * @coversDefaultClass \Drupal\og\GroupPermission
 */
class GroupPermissionTest extends UnitTestCase {

  /**
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getRoles
   *
   * @dataProvider groupPermissionProvider
   */
  public function testGetRoles(array $values) {
    $permission = new GroupPermission($values);
    $this->assertEquals($values['roles'], $permission->getRoles());
  }

  /**
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setRoles
   *
   * @dataProvider groupPermissionProvider
   */
  public function testSetRoles(array $values) {
    $permission = new GroupPermission();
    $permission->setRoles($values['roles']);
    $this->assertEquals($values['roles'], $permission->get('roles'));
  }

  /**
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
   * @covers ::get
   *
   * @expectedException \InvalidArgumentException
   */
  public function testGetInvalidProperty() {
    $permission = new GroupPermission();
    $permission->get('invalid property');
  }

  /**
   * @covers ::set
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidProperty() {
    $permission = new GroupPermission();
    $permission->set('invalid property', 'a value');
  }

  /**
   * @covers ::set
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetInvalidRestrictAccessValue() {
    $permission = new GroupPermission();
    $permission->set('restrict access', 'invalid value');
  }

  /**
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
