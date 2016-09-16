<?php

namespace Drupal\og\ContextProvider;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\OgGroupResolverInterface;

class OgContext implements ContextProviderInterface {

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
   * IDs of cache contexts that are relevant for the current group context.
   *
   * @var string[]
   */
  protected $cacheContextIds = [];

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
    $context = new Context(new ContextDefinition('og', $this->t('Active group')));
    return ['og' => $context];
  }

  protected function getOgContext() {
    $cache_contexts = [];

    $context_definition = new ContextDefinition('og', $this->t('Active group'), FALSE);
    $context = new Context($context_definition, $this->getBestCandidate($cache_contexts));

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts($this->cacheContexts);

    $context->addCacheableDependency($cacheability);
  }

  protected function getBestCandidate() {
    /** @var EntityInterface[] $candidates */
    $candidates = [];
    $plugins = [];

    // Retrieve the list of group resolvers. These are stored in config, and are
    // ordered by priority.
    $group_resolvers = $this->configFactory->get('og.settings')->get('group_resolvers');
    $priority = 0;
    foreach ($group_resolvers as $plugin_id) {
      /** @var OgGroupResolverInterface $plugin */
      if ($plugin = $this->pluginManager->getInstance(['id' => $plugin_id])) {
        $plugins[$plugin_id] = $plugin;

        // Retrieve the "best candidate" in the plugin's domain.
        $best_candidate = $plugin->getBestCandidate();

        // If the plugin is certain that the candidate belongs to the current
        // context, it can declare the search to be over and we can return the
        // candidate. Note that the candidate may even be empty at this point -
        // this means the plugin has discovered with 100% certainty that the
        // current context does NOT have a group.
        if ($plugin->isPropagationStopped()) {
          $this->addCacheContextIds($plugin->getCacheContextIds());
          return $best_candidate;
        }

        // The search continues. Add the plugin's results to the list of
        // potential candidates. If the plugin was not able to provide a "best"
        // candidate, add all of its results instead.
        foreach ($best_candidate ? [$best_candidate] : $plugin->getGroups() as $candidate) {
          $key = $candidate->getEntityTypeId() . '|' . $candidate->id();
          $candidates[$key]['votes'][$plugin_id] = $priority;
          $candidates[$key]['entity'] = $candidate;
        }

        // The next plugin we try will have a lower priority.
        $priority--;
      }
    }

    // None of the plugins has been able to discover a group candidate with 100%
    // certainty. We will iterate over the candidates and return the one that
    // has the most "votes". If there are multiple candidates with the same
    // number of votes then the candidate that was resolved by the plugin(s)
    // with the highest priority will be returned.
    uasort($candidates, function ($a, $b) {
      if (count($a['votes']) == count($b['votes'])) {
        return array_sum($a['votes']) < array_sum($b['votes']) ? -1 : 1;
      }
      return count($a['votes']) < count($b['votes']) ? -1 : 1;
    });

    // We found the best candidate.
    $candidate = reset($candidates);

    // Compile the cache contexts that were used by the plugins that voted for
    // our chosen candidate.
    foreach (array_keys($candidate['votes']) as $plugin_id) {
      $this->addCacheContextIds($plugins[$plugin_id]->getContextIds());
    };

    return $candidate['entity'];
  }

  /**
   * @todo Document.
   */
  protected function addCacheContextIds($contexts) {
    foreach ($contexts as $context) {
      if (!in_array($context, $this->cacheContextIds)) {
        $this->cacheContextIds[] = $context;
      }
    }
  }

}
