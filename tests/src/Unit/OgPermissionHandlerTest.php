<?php

/**
 * @file
 * Contains \Drupal\Tests\og\Unit\OgPermissionHandlerTest.
 */

namespace Drupal\Tests\og\Unit;

use Drupal\Core\Extension\Extension;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\og\OgRoleInterface;
use Drupal\og\OgPermissionHandler;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * Tests OG's permission handler.
 *
 * @group og
 *
 * @coversDefaultClass \Drupal\og\OgPermissionHandler
 * @see \Drupal\Tests\user\Unit\PermissionHandlerTest
 */
class OgPermissionHandlerTest extends UnitTestCase {

  /**
   * The tested permission handler.
   *
   * @var \Drupal\Tests\og\Unit\TestPermissionHandler|\Drupal\og\OgPermissionHandler
   */
  protected $permissionHandler;

  /**
   * The mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked string translation.
   *
   * @var \Drupal\Tests\og\Unit\TestTranslationManager
   */
  protected $stringTranslation;

  /**
   * The mocked controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->stringTranslation = new TestTranslationManager();
    $this->controllerResolver = $this->getMock('Drupal\Core\Controller\ControllerResolverInterface');
  }

  /**
   * Provides an extension object for a given module with a human name.
   *
   * @param string $module
   *   The module machine name.
   * @param string $name
   *   The module human name.
   *
   * @return \Drupal\Core\Extension\Extension
   *   The extension object.
   */
  protected function mockModuleExtension($module, $name) {
    $extension = new Extension($this->root, $module, "modules/$module");
    $extension->info['name'] = $name;
    return $extension;
  }

  /**
   * Tests permissions provided by YML files.
   *
   * @covers ::__construct
   * @covers ::getPermissions
   * @covers ::buildPermissionsYaml
   * @covers ::moduleProvidesPermissions
   */
  public function testBuildPermissionsYaml() {
    vfsStreamWrapper::register();
    $root = new vfsStreamDirectory('modules');
    vfsStreamWrapper::setRoot($root);

    $this->moduleHandler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->moduleHandler->expects($this->once())
      ->method('getModuleDirectories')
      ->willReturn(array(
        'module_a' => vfsStream::url('modules/module_a'),
        'module_b' => vfsStream::url('modules/module_b'),
      ));

    $url = vfsStream::url('modules');
    mkdir($url . '/module_a');
    file_put_contents($url . '/module_a/module_a.og_permissions.yml',
      "access_module_a: single_description"
    );
    mkdir($url . '/module_b');
    file_put_contents($url . '/module_b/module_b.og_permissions.yml',
      "'access module b':
  title: 'Access B'
  description: 'bla bla'
  roles:
    - ANONYMOUS_ROLE
    - AUTHENTICATED_ROLE
    - ADMINISTRATOR_ROLE
  default roles:
    - ANONYMOUS_ROLE
    - AUTHENTICATED_ROLE
    - ADMINISTRATOR_ROLE
");

    $modules = array('module_a', 'module_b');
    $extensions = array(
      'module_a' => $this->mockModuleExtension('module_a', 'Module a'),
      'module_b' => $this->mockModuleExtension('module_b', 'Module b'),
    );

    $this->moduleHandler->expects($this->any())
      ->method('getModuleList')
      ->willReturn(array_flip($modules));

    $this->controllerResolver->expects($this->never())
      ->method('getControllerFromDefinition');

    $this->permissionHandler = new TestPermissionHandler($this->moduleHandler, $this->stringTranslation, $this->controllerResolver);

    // Setup system_rebuild_module_data().
    $this->permissionHandler->setSystemRebuildModuleData($extensions);

    $actual_permissions = $this->permissionHandler->getPermissions();
    $this->assertPermissions($actual_permissions);

    $this->assertTrue($this->permissionHandler->moduleProvidesPermissions('module_a'));
    $this->assertTrue($this->permissionHandler->moduleProvidesPermissions('module_b'));
    $this->assertFalse($this->permissionHandler->moduleProvidesPermissions('module_c'));
  }

  /**
   * Checks that the permissions are like expected.
   *
   * @param array $actual_permissions
   *   The actual permissions
   */
  protected function assertPermissions(array $actual_permissions) {
    $this->assertCount(2, $actual_permissions);

    $this->assertEquals([], $actual_permissions['access_module_a']['default roles']);
    $this->assertEquals('module_a', $actual_permissions['access_module_a']['provider']);
    $this->assertEquals([OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED], $actual_permissions['access_module_a']['roles']);
    $this->assertEquals('single_description', $actual_permissions['access_module_a']['title']);

    $this->assertEquals([OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED, OgRoleInterface::ADMINISTRATOR], $actual_permissions['access module b']['default roles']);
    $this->assertEquals('module_b', $actual_permissions['access module b']['provider']);
    $this->assertEquals([OgRoleInterface::ANONYMOUS, OgRoleInterface::AUTHENTICATED, OgRoleInterface::ADMINISTRATOR], $actual_permissions['access module b']['roles']);
    $this->assertEquals('Access B', $actual_permissions['access module b']['title']);
  }

}

class TestPermissionHandler extends OgPermissionHandler {

  /**
   * Test module data.
   *
   * @var array
   */
  protected $systemModuleData;

  protected function systemRebuildModuleData() {
    return $this->systemModuleData;
  }

  public function setSystemRebuildModuleData(array $extensions) {
    $this->systemModuleData = $extensions;
  }

}

/**
 * Implements a translation manager in tests.
 */
class TestTranslationManager implements TranslationInterface {

  /**
   * {@inheritdoc}
   */
  public function translate($string, array $args = array(), array $options = array()) {
    return new TranslatableMarkup($string, $args, $options, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function translateString(TranslatableMarkup $translated_string) {
    return $translated_string->getUntranslatedString();
  }

  /**
   * {@inheritdoc}
   */
  public function formatPlural($count, $singular, $plural, array $args = array(), array $options = array()) {
    return new PluralTranslatableMarkup($count, $singular, $plural, $args, $options, $this);
  }

}
