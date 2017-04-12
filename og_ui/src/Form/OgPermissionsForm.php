<?php

namespace Drupal\og_ui\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgRoleManagerInterface;
use Drupal\og\PermissionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide the group permissions form.
 */
class OgPermissionsForm extends FormBase {

  /**
   * The permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * The role manager.
   *
   * @var \Drupal\og\OgRoleManagerInterface
   */
  protected $roleManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The group roles.
   *
   * @var array
   */
  protected $roles;

  /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission handler.
   * @param \Drupal\og\OgRoleManagerInterface $role_manager
   *   The OG role manager.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(PermissionManagerInterface $permission_manager, OgRoleManagerInterface $role_manager, GroupTypeManager $group_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->permissionManager = $permission_manager;
    $this->roleManager = $role_manager;
    $this->groupTypeManager = $group_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.permission_manager'),
      $container->get('og.role_manager'),
      $container->get('og.group_type_manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_permissions';
  }

  /**
   * Title callback for the group permissions page.
   *
   * @param string $entity_type
   *   The group entity type id.
   * @param string $bundle
   *   The group bundle id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The group permission title.
   */
  public function titleCallback($entity_type, $bundle) {
    return $this->t('@bundle permissions', [
      '@bundle' => $this->entityTypeBundleInfo->getBundleInfo($entity_type)[$bundle]['label'],
    ]);
  }

  /**
   * Returns the group roles to display in the form.
   *
   * @param string $entity_type
   *   The group entity type id.
   * @param string $bundle
   *   The group entity bundle id.
   *
   * @return array
   *   The group roles.
   */
  public function getGroupRoles($entity_type, $bundle) {
    if (empty($this->roles)) {
      $this->roles = $this->roleManager->getRolesByBundle($entity_type, $bundle);
    }

    return $this->roles;
  }

  /**
   * The group permissions form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $entity_type
   *   The group entity type id.
   * @param string $bundle
   *   The group bundle id.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = '', $bundle = '') {
    // Render the link for hiding descriptions.
    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $hide_descriptions = system_admin_compact_mode();

    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];

    $roles = $this->getGroupRoles($entity_type, $bundle);

    /** @var \Drupal\og\Entity\OgRole $role */
    foreach ($roles as $role) {
      $form['permissions']['#header'][] = [
        'data' => $role->getLabel(),
        'class' => ['checkbox'],
      ];
    }

    $bundles = $this->groupTypeManager->getGroupContentBundleIdsByGroupBundle($entity_type, $bundle);
    $group_permissions = $this->permissionManager->getDefaultGroupPermissions($entity_type, $bundle);
    $group_content_permissions = $this->permissionManager->getDefaultEntityOperationPermissions($entity_type, $bundle, $bundles);

    $permissions_by_provider = [
      'Group' => $group_permissions,
      'Group content' => $group_content_permissions,
    ];

    foreach ($permissions_by_provider as $provider => $permissions) {
      // Provider.
      $form['permissions'][$provider] = [
        [
          '#wrapper_attributes' => [
            'colspan' => count($roles) + 1,
            'class' => ['module'],
            'id' => 'provider-' . $provider,
          ],
          '#markup' => $provider,
        ],
      ];

      /** @var \Drupal\og\Permission $permission */
      foreach ($permissions as $perm => $permission) {
        // Fill in default values for the permission.
        $perm_item = [
          'title' => $permission->getTitle(),
          'description' => $permission->getDescription(),
          'restrict access' => $permission->getRestrictAccess(),
          'warning' => $permission->getRestrictAccess() ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];

        $form['permissions'][$perm]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $perm_item['title'],
          ],
        ];

        // Show the permission description.
        if (!$hide_descriptions) {
          $form['permissions'][$perm]['description']['#context']['description'] = $perm_item['description'];
          $form['permissions'][$perm]['description']['#context']['warning'] = $perm_item['warning'];
        }
        foreach ($roles as $rid => $role) {
          list(,, $rid_simple) = explode('-', $rid, 3);

          // The roles property indicates which roles the permission applies to.
          $permission_applies = TRUE;
          if (property_exists($permission, 'roles')) {
            $target_roles = $permission->get('roles');
            $permission_applies = empty($target_roles) || in_array($rid_simple, $target_roles);
          }

          if ($permission_applies) {
            $form['permissions'][$perm][$rid] = [
              '#title' => $role->getName() . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => [
                'class' => ['checkbox'],
              ],
              '#type' => 'checkbox',
              '#default_value' => $role->hasPermission($perm) ? 1 : 0,
              '#attributes' => ['class' => ['rid-' . $rid, 'js-rid-' . $rid]],
              '#parents' => [$rid, $perm],
            ];

            // Show a column of disabled but checked checkboxes.
            // Only applies to admins or default roles.
            if ($roles[$rid]->get('is_admin') ||
                in_array($rid_simple, $permission->getDefaultRoles())) {
              $form['permissions'][$perm][$rid]['#disabled'] = TRUE;
              $form['permissions'][$perm][$rid]['#default_value'] = TRUE;
            }
          }
          else {
            $form['permissions'][$perm][$rid] = [
              '#title' => $role->getName() . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => [
                'class' => ['checkbox'],
              ],
              '#markup' => '-',
            ];
          }
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'user/drupal.user.permissions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\og\Entity\OgRole $role */
    foreach ($this->roles as $rid => $role) {
      if (!$form_state->hasValue($rid)) {
        continue;
      }

      $permissions = $form_state->getValue($rid);
      foreach ($permissions as $permission => $grant) {
        if ($grant) {
          $role->grantPermission($permission);
        }
        else {
          $role->revokePermission($permission);
        }
      }

      $role->save();
    }
  }

}
