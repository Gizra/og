<?php

namespace Drupal\og_ui\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the main administration settings form for Organic groups.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * The manager for OgDeleteOrphans plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $ogDeleteOrphansPluginManager;

  /**
   * Constructs an AdminSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $delete_orphans_plugin_manager
   *   The manager for OgDeleteOrphans plugins.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $delete_orphans_plugin_manager) {
    parent::__construct($config_factory);
    $this->ogDeleteOrphansPluginManager = $delete_orphans_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.og.delete_orphans')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_ui_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'og.settings',
      'og_ui.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config_og = $this->config('og.settings');

    $form['og_group_manager_full_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group manager has full permissions'),
      '#description' => $this->t('When enabled the group manager will have all the permissions in the group.'),
      '#default_value' => $config_og->get('group_manager_full_access'),
    ];

    $form['og_node_access_strict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict node access permissions'),
      '#description' => $this->t('When enabled Organic groups will restrict permissions for creating, updating and deleting according to the Organic groups access settings. Example: A content editor with the <em>Edit any page content</em> permission who is not a member of a group would be denied access to modifying page content in that group. (For restricting view access use the Organic groups access control module.)'),
      '#default_value' => $config_og->get('node_access_strict'),
    ];

    // @todo: Port og_ui_admin_people_view.
    $form['og_delete_orphans'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete orphans'),
      '#description' => $this->t('Delete orphaned group content (excluding users) when a group is deleted.'),
      '#default_value' => $config_og->get('delete_orphans'),
    ];

    $definitions = $this->ogDeleteOrphansPluginManager->getDefinitions();
    ksort($definitions);
    $options = array_map(function ($definition) {
      return $definition['label'];
    }, $definitions);

    $form['og_delete_orphans_plugin_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Deletion method'),
      '#default_value' => $config_og->get('delete_orphans_plugin_id'),
      '#options' => $options,
      '#states' => [
        'visible' => [
          ':input[name="og_delete_orphans"]' => ['checked' => TRUE],
        ],
      ],
      '#attributes' => ['class' => ['child-item']],
    ];

    foreach ($definitions as $id => $definition) {
      /** @var \Drupal\og\OgDeleteOrphansInterface $plugin */
      $plugin = $this->ogDeleteOrphansPluginManager->createInstance($id, []);

      // Add the description for each delete method.
      $form['og_delete_orphans_plugin_id'][$id] = [
        '#description' => $definition['description'],
      ];

      // Show the configuration options for the chosen plugin.
      $configuration = $plugin->configurationForm($form, $form_state);
      if ($configuration) {
        $form['og_delete_orphans_options_' . $id] = $configuration + [
          '#states' => [
            'visible' => [
              ':input[name="og_delete_orphans"]' => ['checked' => TRUE],
              ':input[name="og_delete_orphans_plugin_id"]' => ['value' => $id],
            ],
          ],
        ];
      }
    }

    $form['#attached']['library'][] = 'og_ui/form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('og.settings')
      ->set('group_manager_full_access', $form_state->getValue('og_group_manager_full_access'))
      ->set('node_access_strict', $form_state->getValue('og_node_access_strict'))
      ->set('delete_orphans', $form_state->getValue('og_delete_orphans'))
      ->set('delete_orphans_plugin_id', $form_state->getValue('og_delete_orphans_plugin_id'))
      ->save();
  }

}
