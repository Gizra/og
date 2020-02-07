<?php

namespace Drupal\Tests\og\Unit;

use Drupal\og\GroupContentOperationPermission;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests group content permissions.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\GroupContentOperationPermission
 */
class GroupContentOperationPermissionTest extends UnitTestCase {

  /**
   * Tests getting the entity type for the group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getEntityType
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetEntityType(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['entity type'], $permission->getEntityType());
  }

  /**
   * Tests setting the entity type for the group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setEntityType
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetEntityType(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setEntityType($values['entity type']);
    $this->assertEquals($values['entity type'], $permission->get('entity type'));
  }

  /**
   * Tests getting the bundle from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getBundle
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetBundle(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['bundle'], $permission->getBundle());
  }

  /**
   * Tests setting the bundle for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setBundle
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetBundle(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setBundle($values['bundle']);
    $this->assertEquals($values['bundle'], $permission->get('bundle'));
  }

  /**
   * Tests getting the operation from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getOperation
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetOperation(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['operation'], $permission->getOperation());
  }

  /**
   * Tests setting the operation from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setOperation
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetOperation(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setOperation($values['operation']);
    $this->assertEquals($values['operation'], $permission->get('operation'));
  }

  /**
   * Tests getting the ownership property from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getOwner
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetOwnership(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['owner'], $permission->getOwner());
  }

  /**
   * Tests setting the ownership property for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setOwner
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetOwnership(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setOwner($values['owner']);
    $this->assertEquals($values['owner'], $permission->get('owner'));
  }

  /**
   * Tests getting a property from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::get
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGet(array $values) {
    $permission = new GroupContentOperationPermission($values);
    foreach ($values as $property => $value) {
      $this->assertEquals($value, $permission->get($property));
    }
  }

  /**
   * Tests setting a property for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::set
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSet(array $values) {
    $permission = new GroupContentOperationPermission();
    foreach ($values as $property => $value) {
      $permission->set($property, $value);
      $this->assertEquals($value, $permission->get($property));
    }
  }

  /**
   * Tests getting the name property from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getName
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetName(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['name'], $permission->getName());
  }

  /**
   * Tests setting the name property for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setName
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetName(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setName($values['name']);
    $this->assertEquals($values['name'], $permission->get('name'));
  }

  /**
   * Tests getting the title property from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getTitle
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetTitle(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['title'], $permission->getTitle());
  }

  /**
   * Tests setting the title property for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setTitle
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetTitle(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setTitle($values['title']);
    $this->assertEquals($values['title'], $permission->get('title'));
  }

  /**
   * Tests getting the description property from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getDescription
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetDescription(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['description'], $permission->getDescription());
  }

  /**
   * Tests setting the description property for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setDescription
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetDescription(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setDescription($values['description']);
    $this->assertEquals($values['description'], $permission->get('description'));
  }

  /**
   * Tests getting the default roles from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getDefaultRoles
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetDefaultRoles(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['default roles'], $permission->getDefaultRoles());
  }

  /**
   * Tests setting the default roles for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setDefaultRoles
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetDefaultRoles(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setDefaultRoles($values['default roles']);
    $this->assertEquals($values['default roles'], $permission->get('default roles'));
  }

  /**
   * Tests getting the restrict access property from a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::getRestrictAccess
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testGetRestrictAccess(array $values) {
    $permission = new GroupContentOperationPermission($values);
    $this->assertEquals($values['restrict access'], $permission->getRestrictAccess());
  }

  /**
   * Tests setting the restrict access property for a group content permission.
   *
   * @param array $values
   *   Associative array of test values, keyed by property name.
   *
   * @covers ::setRestrictAccess
   *
   * @dataProvider groupContentOperationPermissionProvider
   */
  public function testSetRestrictAccess(array $values) {
    $permission = new GroupContentOperationPermission();
    $permission->setRestrictAccess($values['restrict access']);
    $this->assertEquals($values['restrict access'], $permission->get('restrict access'));
  }

  /**
   * Tests getting an invalid property from a group content permission.
   *
   * @covers ::get
   */
  public function testGetInvalidProperty() {
    $permission = new GroupContentOperationPermission();

    $this->expectException(\InvalidArgumentException::class);
    $permission->get('invalid property');
  }

  /**
   * Tests setting an invalid property for a group content permission.
   *
   * @covers ::set
   */
  public function testSetInvalidProperty() {
    $permission = new GroupContentOperationPermission();

    $this->expectException(\InvalidArgumentException::class);
    $permission->set('invalid property', 'a value');
  }

  /**
   * Tests setting an invalid restrict access for a group content permission.
   *
   * @covers ::set
   */
  public function testSetInvalidRestrictAccessValue() {
    $permission = new GroupContentOperationPermission();

    $this->expectException(\InvalidArgumentException::class);
    $permission->set('restrict access', 'invalid value');
  }

  /**
   * Tests setting an invalid ownership property for a group content permission.
   *
   * @covers ::set
   */
  public function testSetInvalidOwnershipValue() {
    $permission = new GroupContentOperationPermission();

    $this->expectException(\InvalidArgumentException::class);
    $permission->set('owner', 'invalid value');
  }

  /**
   * Data provider; Array with group content permissions.
   *
   * @return array
   *   An array of test data, each data set consisting of an associative array
   *   of permission values, keyed by property name.
   */
  public function groupContentOperationPermissionProvider() {
    return [
      [
        [
          'name' => 'edit own article content',
          'title' => $this->t('Article: Edit own content'),
          'description' => $this->t('Allows to update own article content'),
          'default roles' => [OgRoleInterface::ADMINISTRATOR],
          'restrict access' => FALSE,
          'entity type' => 'node',
          'bundle' => 'article',
          'operation' => 'update',
          'owner' => TRUE,
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
