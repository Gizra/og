<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Tests\UnitTestCase;

/**
 * Base class for testing OgGroupResolver plugins.
 *
 * @group og
 */
abstract class OgGroupResolverTestBase extends UnitTestCase {

  /**
   * The fully qualified class name of the plugin under test.
   *
   * @var string
   */
  protected $className;

  /**
   * The ID of the plugin under test.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Tests the groups that are resolved by the plugin.
   *
   * @dataProvider resolveProvider
   * @covers ::resolve()
   */
  abstract public function testResolve();

  /**
   * Tests if the plugin is able to stop the group resolving process.
   *
   * @covers ::isPropagationStopped
   * @covers ::stopPropagation
   */
  public function testStopPropagation() {
    $plugin = $this->getPluginInstance();

    // Initially propagation should not be stopped.
    $this->assertFalse($plugin->isPropagationStopped());

    // Test if propagation can be stopped.
    $plugin->stopPropagation();
    $this->assertTrue($plugin->isPropagationStopped());
  }

  /**
   * Returns an instance of the plugin under test.
   *
   * @return \Drupal\og\OgGroupResolverInterface
   *   The plugin under test.
   */
  protected function getPluginInstance() {
    $args = array_merge([
      [],
      $this->pluginId,
      [
        'id' => $this->pluginId,
        'class' => $this->className,
        'provider' => 'og',
      ],
    ], $this->getInjectedDependencies());
    return new $this->className(...$args);
  }

  /**
   * Returns the mocked classes that the plugin depends on.
   *
   * @return array
   *   The mocked dependencies.
   */
  protected function getInjectedDependencies() {
    return [];
  }

}
