<?php

namespace Drupal\og\ContextProvider;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\OgContextInterface;
use Drupal\og\OgResolvedGroupCollection;

/**
 * Provides the group that best matches the current context.
 *
 * There might be several groups that are relevant in the current context, and
 * this class tries to determine which is the best possible candidate.
 *
 * For example, if we are on a page that displays a group content entity that
 * belongs to two groups, then both groups are relevant in the current
 * context. We might then decide which group is the better candidate by
 * looking at a URL query argument or inspecting the user's browsing history
 * to see if they are coming from a group page.
 *
 * This discovery of groups is handled by OgGroupResolver plugins. Each plugin
 * is responsible for discovering groups in a specific domain (e.g. find all
 * groups belonging to the current route). The plugins that will be used for
 * the discovery are configurable; they are listed under the 'group_resolvers'
 * key in the 'og.settings' config. The plugins are ordered by priority,
 * meaning that if two groups are relevant to the current context, then the
 * plugin with the highest priority will decide which group is going to 'win'.
 *
 * Developers can customize the group context result by providing their own
 * plugins and by activating, disabling or reordering the default ones.
 */
class OgContext implements OgContextInterface, ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The OgGroupResolver plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new OgContext.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The OgGroupResolver plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(PluginManagerInterface $plugin_manager, ConfigFactoryInterface $config_factory) {
    $this->pluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    // Don't bother to resolve the group context if it is not requested.
    if (!in_array('og', $unqualified_context_ids)) {
      return [];
    }

    return ['og' => $this->getOgContext()];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity', $this->t('Active group')));
    return ['og' => $context];
  }

  /**
   * Returns the context object containing the relevant group.
   *
   * @return \Drupal\Core\Plugin\Context\Context
   *   A context object containing the group which is relevant in the current
   *   context as a value. If there is no relevant group in the current context
   *   then the value will be empty.
   */
  protected function getOgContext() {
    $context_definition = new ContextDefinition('entity', $this->t('Active group'), FALSE);
    $candidate = $this->getBestCandidate();
    $group = !empty($candidate['entity']) ? $candidate['entity'] : NULL;
    $context = new Context($context_definition, $group);

    $cacheability = new CacheableMetadata();
    if (!empty($candidate['cache_contexts'])) {
      $cacheability->setCacheContexts($candidate['cache_contexts']);
    }
    $context->addCacheableDependency($cacheability);

    return $context;
  }

  /**
   * Returns information about the group which best matches the current context.
   *
   * @return array|null
   *   An associative array with information about the chosen candidate. It has
   *   the following keys:
   *   - entity: the group entity.
   *   - votes: an array of votes that have been cast for this entity.
   *   - cache_contexts: an array of cache contexts that were used to discover
   *     this group.
   *   If no group was found in the current context, NULL is returned.
   *
   * @see \Drupal\og\OgGroupResolverInterface
   */
  protected function getBestCandidate() {
    $collection = new OgResolvedGroupCollection();

    // Retrieve the list of group resolvers. These are stored in config, and are
    // ordered by priority.
    $group_resolvers = $this->configFactory->get('og.settings')->get('group_resolvers');
    $priority = 0;
    foreach ($group_resolvers as $plugin_id) {
      /** @var \Drupal\og\OgGroupResolverInterface $plugin */
      if ($plugin = $this->pluginManager->createInstance($plugin_id)) {
        // Set the default vote weight according to the plugin's priority.
        $collection->setVoteWeight($priority);

        // Let the plugin do its magic.
        $plugin->resolve($collection);

        // If the plugin is certain that the candidate belongs to the current
        // context, it can declare the search to be over.
        if ($plugin->isPropagationStopped()) {
          break;
        }

        // The next plugin we try will have a lower priority.
        $priority--;
      }
    }

    // Sort the resolved groups and retrieve the first result, this will be the
    // best candidate.
    $collection->sort();
    $group_info = $collection->getGroupInfo();

    if (!empty($group_info)) {
      return reset($group_info);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    $contexts = $this->getRuntimeContexts(['og']);
    if (!empty($contexts['og']) && $group = $contexts['og']->getContextValue()) {
      if ($group instanceof ContentEntityInterface) {
        return $group;
      }
    }
  }

}
