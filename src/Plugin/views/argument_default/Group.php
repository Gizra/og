<?php

namespace Drupal\og\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to provide the group from the current context.
 *
 * @ViewsArgumentDefault(
 *   id = "og_group_context",
 *   title = @Translation("Group ID from OG context")
 * )
 */
class Group extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The OG context provider.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $ogContext;

  /**
   * Constructs a new User instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $og_context
   *   The OG context provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $og_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->ogContext = $og_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.context')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['og_group_context'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['og_group_context'] = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $group = $this->getGroup();
    if ($group instanceof ContentEntityInterface) {
      return $group->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // @todo This should return the active group cache context as soon as we
    //   have it.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $group = $this->getGroup();
    if ($group instanceof ContentEntityInterface) {
      return Cache::buildTags('og-group-content', $group->getCacheTagsToInvalidate());
    }
    return [];
  }

  /**
   * Returns the group that is relevant in the current context.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The group, or NULL if no group is found.
   */
  protected function getGroup() {
    $contexts = $this->ogContext->getRuntimeContexts(['og']);
    if (!empty($contexts['og']) && $group = $contexts['og']->getContextValue()) {
      if ($group instanceof ContentEntityInterface) {
        return $group;
      }
    }
  }

}
