<?php

namespace Drupal\Tests\og\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgRoleInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the OgMembership entity.
 *
 * @group og
 * @coversDefaultClass \Drupal\og\Entity\OgMembership
 */
class OgMembershipTest extends UnitTestCase {

  /**
   * OgRole entity.
   *
   * @var \Drupal\og\Entity\OgRole|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ogRole;


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->ogRole = $this->prophesize(OgRole::class);

    // Set the container for the string translation service.
    $container = new ContainerBuilder();
    $container->set('og.roles', $this->ogRole->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Tests setting OG roles.
   *
   * @covers ::setRoles
   */
  public function testSetRoles() {
    $roles = $this->ogRole->loadMultiple();
    $membership = OgMembership::create();

    $membership->setRoles($roles);

    $actual_roles = $membership->getRoles();
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

}
