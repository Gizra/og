<?php

namespace Drupal\Tests\og\Unit\Plugin\OgGroupResolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgGroupResolverInterface;

/**
 * Base class for testing OgGroupResolver plugins that depend on the route.
 */
abstract class OgRouteGroupResolverTestBase extends OgGroupResolverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $pluginType = OgGroupResolverInterface::PROVIDER;

  /**
   * {@inheritdoc}
   */
  public function testGetCacheContextIds() {
    $plugin = $this->getPluginInstance();
    $this->assertEquals(['route'], $plugin->getCacheContextIds());
  }

  /**
   * {@inheritdoc}
   */
  public function testGetResolverType() {
    $plugin = $this->getPluginInstance();
    $this->assertEquals(OgGroupResolverInterface::PROVIDER, $plugin->getResolverType());
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginInstance(array $args = []) {
    $args = $args ?: $this->getInjectedDependencies();
    return parent::getPluginInstance($args);
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
