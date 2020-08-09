<?php

namespace Drupal\og\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgContextInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that shows the number of members in the current group.
 *
 * This block is mainly intended to demonstrate the group membership list cache
 * tag but can also be used to show the number of members on group pages. The
 * way the text is displayed can be changed by overriding the Twig template.
 *
 * @Block(
 *   id = "og_member_count",
 *   admin_label = @Translation("Group member count")
 * )
 */
class MemberCountBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The OG context provider.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a MemberCountBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context provider.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OgContextInterface $og_context, MembershipManagerInterface $membership_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->ogContext = $og_context;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.context'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'count_blocked_users' => FALSE,
      'count_pending_users' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['count_blocked_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count blocked users'),
      '#default_value' => $this->configuration['count_blocked_users'],
    ];

    $form['count_pending_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count pending users'),
      '#default_value' => $this->configuration['count_pending_users'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (array_keys($this->defaultConfiguration()) as $setting) {
      $this->configuration[$setting] = $form_state->getValue($setting);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Do not render anything if there is no group in the current context.
    $group = $this->ogContext->getGroup();
    if (empty($group)) {
      return [];
    }

    $states = [OgMembershipInterface::STATE_ACTIVE];

    if ($this->configuration['count_blocked_users']) {
      $states[] = OgMembershipInterface::STATE_BLOCKED;
    }

    if ($this->configuration['count_pending_users']) {
      $states[] = OgMembershipInterface::STATE_PENDING;
    }

    $membership_ids = $this->membershipManager->getGroupMembershipIdsByRoleNames($group, [OgRoleInterface::AUTHENTICATED], $states);

    return [
      '#theme' => 'og_member_count',
      '#count' => count($membership_ids),
      '#group' => $group,
      '#group_label' => $group->label(),
      '#membership_states' => $states,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    $group = $this->ogContext->getGroup();
    if (!empty($group)) {
      $tags = Cache::mergeTags(Cache::buildTags(OgMembershipInterface::GROUP_MEMBERSHIP_LIST_CACHE_TAG_PREFIX, $group->getCacheTagsToInvalidate()), $tags);
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['og_group_context']);
  }

}
