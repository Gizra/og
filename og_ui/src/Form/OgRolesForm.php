<?php

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\og\GroupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to manage group roles.
 */
class OgRolesForm extends FormBase {

  /**
   * The OG group manager.
   *
   * @var \Drupal\og\GroupManager
   */
  protected $groupManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs an OgRolesForm.
   *
   * @param \Drupal\og\GroupManager $group_manager
   *   The OG group manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(GroupManager $group_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    // @todo: Used?
    $this->groupManager = $group_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.group.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_ui_roles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = '', $bundle = '') {
    $header = [t('Role name'), ['data' => t('Operations'), 'colspan' => 2]];
    $rows = [];
    $properties = [
      'group_type' => $entity_type,
      'group_bundle' => $bundle,
    ];
    /** @var \Drupal\og\Entity\OgRole $role */
    foreach ($this->entityTypeManager->getStorage('og_role')->loadByProperties($properties) as $role) {
      // Add the role name cell.
      $columns = [['data' => $role->getLabel()]];

      // Add the edit role link if the role is editable.
      if ($role->isMutable()) {
        $columns[] = [
          'data' => Link::createFromRoute(t('Edit role'), 'og_ui.roles_edit_form', [
            'og_role' => $role->id(),
          ]),
        ];
      }
      else {
        $columns[] = ['data' => t('Locked')];
      }

      // Add the edit permissions link.
      $columns[] = [
        'data' => Link::createFromRoute(t('Edit permissions'), 'og_ui.permissions_edit_form', [
          'og_role' => $role->id(),
        ]),
      ];

      $rows[] = $columns;
    }

    $form['roles_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No roles available.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    throw new \Exception('Implement ' . __METHOD__);
  }

  /**
   * Title callback for the form.
   *
   * @param string $entity_type
   *   The group entity type.
   * @param string $bundle
   *   The group bundle.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function titleCallback($entity_type, $bundle) {
    return $this->t('OG @type - @bundle roles', [
      '@type' => $this->entityTypeManager->getDefinition($entity_type)->getLabel(),
      '@bundle' => $this->entityTypeBundleInfo->getBundleInfo($entity_type)[$bundle]['label'],
    ]);
  }

}
