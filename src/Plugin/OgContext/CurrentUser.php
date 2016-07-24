<?php

namespace Drupal\og\Plugin\OgContext;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\og\OgContextBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @OgContext(
 *  id = "current_user",
 *  label = "Current user",
 *  description = @Translation("Get the group from the current logged in user.")
 * )
 */
class CurrentUser extends OgContextBase {

  /**
   * The group manager service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
  }

}
