<?php

namespace Drupal\og;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for OG context plugins.
 */
abstract class OgContextBase extends PluginBase implements OgContextInterface, ContainerFactoryPluginInterface {

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Gets runtime context values for the given context IDs.
   *
   * For context-aware plugins to function correctly, all of the contexts that
   * they require must be populated with values. So this method should set a
   * value for each context that it adds. For example:
   *
   * @code
   *   // Determine a specific node to pass as context to a block.
   *   $node = ...
   *
   *   // Set that specific node as the value of the 'node' context.
   *   $context = new Context(new ContextDefinition('entity:node'), $node);
   *   return ['node' => $context];
   * @endcode
   *
   * On the other hand, there are cases, on which providers no longer are
   * possible to provide context objects, even without the value, so the caller
   * should not expect it.
   *
   * @param string[] $unqualified_context_ids
   *   The requested context IDs. The context provider must only return contexts
   *   for those IDs.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The determined available contexts, keyed by the unqualified context_id.
   *
   * @see \Drupal\Core\Plugin\Context\ContextProviderInterface:getAvailableContexts()
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // TODO: Implement getRuntimeContexts() method.
  }

  /**
   * Gets all available contexts for the purposes of configuration.
   *
   * When a context aware plugin is being configured, the configuration UI must
   * know which named contexts are potentially available, but does not care
   * about the value, since the value can be different for each request, and
   * might not be available at all during the configuration UI's request.
   *
   * For example:
   * @code
   *   // During configuration, there is no specific node to pass as context.
   *   // However, inform the system that a context named 'node' is
   *   // available, and provide its definition, so that context aware plugins
   *   // can be configured to use it. When the plugin, for example a block,
   *   // needs to evaluate the context, the value of this context will be
   *   // supplied by getRuntimeContexts().
   *   $context = new Context(new ContextDefinition('entity:node'));
   *   return ['node' => $context];
   * @endcode
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   All available contexts keyed by the unqualified context ID.
   *
   * @see \Drupal\Core\Plugin\Context\ContextProviderInterface::getRuntimeContext()
   */
  public function getAvailableContexts() {
    // TODO: Implement getAvailableContexts() method.
  }

}
