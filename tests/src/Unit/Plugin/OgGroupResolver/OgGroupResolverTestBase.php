<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Tests\UnitTestCase;

/**
 * Base class for testing OgGroupResolver plugins.
 *
 * @group og
 */
abstract class OgGroupResolverTestBase extends UnitTestCase  {

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
   * The plugin type of the plugin under test. Either 'provider' or 'selector'.
   *
   * @var string
   */
  protected $pluginType;

  /**
   * Tests the groups that are resolved by the plugin.
   *
   * @dataProvider getGroupsProvider
   * @covers ::getGroups
   */
  abstract public function testGetGroups();

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
   * Tests if the plugin returns the correct cache context IDs.
   *
   * @covers ::getCacheContextIds
   */
  abstract public function testGetCacheContextIds();

  /**
   * Tests if the plugin returns the correct resolver type.
   *
   * @covers ::getResolverType
   */
  abstract public function testGetResolverType();

  /**
   * Returns an instance of the plugin under test.
   *
   * @param array $args
   *   Optional arguments to pass to the plugin constructor. This excludes the
   *   arguments $configuration, $plugin_id, $plugin_definition.
   *
   * @return \Drupal\og\OgGroupResolverInterface
   *   The plugin under test.
   */
  protected function getPluginInstance(array $args = []) {
    $args = array_merge([
      [],
      $this->pluginId,
      [
        'id' => $this->pluginId,
        'type' => $this->pluginType,
        'class' => $this->className,
        'provider' => 'og',
      ],
    ], $args);
    return new $this->className(...$args);
  }

}
