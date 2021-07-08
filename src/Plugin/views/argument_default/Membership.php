<?php

declare(strict_types = 1);

namespace Drupal\og\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\og\MembershipManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the group memberships from the current context.
 *
 * @ViewsArgumentDefault(
 *   id = "og_group_membership",
 *   title = @Translation("Group memberships from current user")
 * )
 */
class Membership extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The OG context provider.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $ogContext;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembership;

  /**
   * The user to be evaluated.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $ogUser;

  /**
   * Constructs a new Membership instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $og_context
   *   The OG context provider.
   * @param \Drupal\og\MembershipManagerInterface $og_membership
   *   The OG membership manager.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to be evaluated.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $og_context, MembershipManagerInterface $og_membership, AccountInterface $user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->ogContext = $og_context;
    $this->ogMembership = $og_membership;
    $this->ogUser = $user;
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
      $container->get('og.membership_manager'),
      $container->get('current_user')->getAccount()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['og_group_membership'] = ['default' => ''];
    $options['entity_type'] = ['default' => 'node'];
    $options['role_ids'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['og_group_membership'] = [];
    $form['entity_type'] = [
      '#type' => 'text',
      '#title' => $this->t('Entity type'),
      '#default_value' => $this->options['entity_type'],
    ];
    $form['role_ids'] = [
      '#type' => 'text',
      '#title' => $this->t('Roles'),
      '#default_value' => $this->options['role_ids'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return implode(',', $this->getCurrentUserGroupIds());
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
    // This cache context is the best thing we have right now.
    // og_role takes in consideration the user memberships and
    // the roles held in the corresponding groups, and while it
    // is one level too granular, i.e. the context will be more
    // fragmented than strictly needed, it works.
    return ['og_role'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $group = $this->getGroup();
    if ($group instanceof ContentEntityInterface) {
      $tag = $group->getEntityTypeId() . ':' . $group->id();
      return Cache::buildTags('og-group-content', [$tag]);
    }

    return [];
  }

  /**
   * Returns groups that current user is a member of.
   *
   * @return array
   *   An array of groups, or an empty array if no group is found.
   */
  protected function getCurrentUserGroupIds() {
    $entity_type = $this->options['entity_type'];
    $role_ids = [];
    if ($this->options['role_ids'] !== '') {
      $role_ids = explode(',', $this->options['role_ids']);
    }
    $groups = $this->ogMembership->getUserGroupIdsByRoleIds($this->ogUser->id(), $role_ids, [OgMembershipInterface::STATE_ACTIVE], FALSE);
    if (!empty($groups) && isset($groups[$entity_type])) {
      return $groups[$entity_type];
    }

    return [];
  }

  /**
   * Returns the group from the runtime context.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The group from context if found.
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
